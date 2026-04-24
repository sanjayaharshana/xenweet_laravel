<?php

namespace Modules\SslTls\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use App\Models\HostingSslStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Modules\SslTls\Services\SslTlsOpenSslService;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class SslTlsController extends Controller
{
    public const TOOL_TABS = ['hosts', 'key', 'csr', 'cert'];

    public function index(Request $request, Hosting $hosting): View
    {
        $host = $hosting->siteHost();
        $tab = $request->query('tab', 'key');
        if (! in_array($tab, self::TOOL_TABS, true)) {
            $tab = 'key';
        }

        $store = $hosting->sslStore;
        $sslSan = $store?->san_hostnames;
        if (! is_array($sslSan)) {
            $sslSan = [];
        }

        return view('ssltls::index', [
            'hosting' => $hosting,
            'httpsUrl' => 'https://'.$host,
            'httpUrl' => 'http://'.$host,
            'activeToolTab' => $tab,
            'sslStore' => $store,
            'sslSanHostnames' => $sslSan,
            'sslSanHostnamesText' => implode("\n", $sslSan),
        ]);
    }

    public function updateSanHostnames(Request $request, Hosting $hosting): RedirectResponse
    {
        $request->validate([
            'san_hostnames' => 'nullable|string|max:131072',
        ]);

        $primary = $hosting->siteHost();
        $raw = (string) $request->input('san_hostnames', '');
        $lines = preg_split('/\R/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $seen = [];
        $san = [];

        foreach ($lines as $line) {
            $h = Hosting::normalizeDomainName($line);
            if ($h === '') {
                continue;
            }
            if (mb_strlen($h) > 253) {
                return $this->sslTlsErrorRedirect(
                    $hosting,
                    'hosts',
                    'Each hostname must be at most 253 characters.'
                );
            }
            if (! preg_match('/^[a-zA-Z0-9.\-]+$/', $h)) {
                return $this->sslTlsErrorRedirect(
                    $hosting,
                    'hosts',
                    'Invalid hostname: use letters, numbers, dots, and hyphens only ('.$h.').'
                );
            }
            if (strcasecmp($h, $primary) === 0) {
                continue;
            }
            $k = strtolower($h);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $san[] = $h;
        }

        sort($san, SORT_NATURAL | SORT_FLAG_CASE);

        HostingSslStore::updateOrCreate(
            ['hosting_id' => $hosting->id],
            ['san_hostnames' => array_values($san)]
        );

        return redirect()
            ->route('hosts.ssl-tls', ['hosting' => $hosting, 'tab' => 'hosts'])
            ->with('ssltls_success', 'SSL host names (SANs) saved. They are stored on this account for your CSR and certificate planning.');
    }

    public function saveCertificate(Request $request, Hosting $hosting): RedirectResponse
    {
        $validated = $request->validate([
            'certificate_pem' => 'nullable|string|max:524288',
            'certificate_chain_pem' => 'nullable|string|max:524288',
        ]);

        $cert = trim((string) ($validated['certificate_pem'] ?? ''));
        $chain = trim((string) ($validated['certificate_chain_pem'] ?? ''));

        HostingSslStore::updateOrCreate(
            ['hosting_id' => $hosting->id],
            [
                'certificate_pem' => $cert === '' ? null : $cert,
                'certificate_chain_pem' => $chain === '' ? null : $chain,
            ]
        );

        return redirect()
            ->route('hosts.ssl-tls', ['hosting' => $hosting, 'tab' => 'cert'])
            ->with('ssltls_success', 'Certificate and chain saved to the database. Install them on the web server in a separate step.');
    }

    public function installCertificate(Hosting $hosting): RedirectResponse
    {
        $store = $hosting->sslStore;
        if (! $store) {
            return $this->sslTlsErrorRedirect($hosting, 'cert', 'No SSL data found for this hosting account.');
        }

        $privateKey = trim((string) ($store->private_key_pem ?? ''));
        $certificate = trim((string) ($store->certificate_pem ?? ''));
        $chain = trim((string) ($store->certificate_chain_pem ?? ''));

        if ($privateKey === '' || ! str_contains($privateKey, 'PRIVATE KEY')) {
            return $this->sslTlsErrorRedirect($hosting, 'cert', 'Saved private key is missing or invalid.');
        }
        if ($certificate === '' || ! str_contains($certificate, 'BEGIN CERTIFICATE')) {
            return $this->sslTlsErrorRedirect($hosting, 'cert', 'Saved certificate is missing or invalid.');
        }

        try {
            $sslDir = $this->resolveSslInstallDirectory($hosting);
            $base = preg_replace('/[^a-zA-Z0-9.\-_]/', '-', $hosting->siteHost()) ?: 'host-'.$hosting->id;

            $keyPath = $sslDir.DIRECTORY_SEPARATOR.$base.'.key.pem';
            $certPath = $sslDir.DIRECTORY_SEPARATOR.$base.'.cert.pem';
            $chainPath = $sslDir.DIRECTORY_SEPARATOR.$base.'.chain.pem';
            $fullchainPath = $sslDir.DIRECTORY_SEPARATOR.$base.'.fullchain.pem';

            File::put($keyPath, $privateKey."\n");
            @chmod($keyPath, 0600);

            File::put($certPath, $certificate."\n");
            @chmod($certPath, 0644);

            $fullchain = $certificate."\n";
            if ($chain !== '') {
                File::put($chainPath, $chain."\n");
                @chmod($chainPath, 0644);
                $fullchain .= trim($chain)."\n";
            } elseif (File::exists($chainPath)) {
                File::delete($chainPath);
            }

            File::put($fullchainPath, $fullchain);
            @chmod($fullchainPath, 0644);

            $nginxMessage = $this->runNginxSslInstall($hosting, $sslDir, $keyPath, $fullchainPath);
        } catch (Throwable $e) {
            return $this->sslTlsErrorRedirect($hosting, 'cert', 'Install failed: '.$e->getMessage());
        }

        return redirect()
            ->route('hosts.ssl-tls', ['hosting' => $hosting, 'tab' => 'cert'])
            ->with('ssltls_success', 'Certificate installed to '.$sslDir.'. '.$nginxMessage);
    }

    public function generatePrivateKey(
        Request $request,
        Hosting $hosting,
        SslTlsOpenSslService $ssl
    ): RedirectResponse {
        $validated = $request->validate([
            'key_type' => 'required|in:rsa2048,ec256',
        ]);

        try {
            $pem = $ssl->generatePrivateKey($validated['key_type']);
        } catch (Throwable $e) {
            return $this->sslTlsErrorRedirect($hosting, 'key', $e->getMessage());
        }

        HostingSslStore::updateOrCreate(
            ['hosting_id' => $hosting->id],
            [
                'private_key_pem' => $pem,
                'key_type' => $validated['key_type'],
                'csr_pem' => null,
                'certificate_pem' => null,
                'certificate_chain_pem' => null,
            ]
        );

        return redirect()
            ->route('hosts.ssl-tls', ['hosting' => $hosting, 'tab' => 'key'])
            ->with('ssltls_key_pem', $pem)
            ->with('ssltls_success', 'Private key generated. It is stored encrypted in the database; copy it below for a secure backup off this panel.');
    }

    public function generateCsr(
        Request $request,
        Hosting $hosting,
        SslTlsOpenSslService $ssl
    ): RedirectResponse {
        $validated = $request->validate([
            'private_key' => 'required|string|min:32|max:262144',
            'common_name' => ['required', 'string', 'max:253', 'regex:/^[a-zA-Z0-9.\-]+$/'],
            'country' => ['nullable', 'string', 'regex:/^$|^[A-Za-z]{2}$/'],
            'state' => ['nullable', 'string', 'max:128', 'regex:/^[\pL\pN\s\-\'.]+$/u'],
            'locality' => ['nullable', 'string', 'max:128', 'regex:/^[\pL\pN\s\-\'.]+$/u'],
            'organization' => ['nullable', 'string', 'max:64', 'regex:/^[\pL\pN\s\-\'.&]+$/u'],
            'organizational_unit' => ['nullable', 'string', 'max:64', 'regex:/^[\pL\pN\s\-\'.&]+$/u'],
            'email' => 'nullable|email|max:254',
        ]);

        $c = null;
        $rawCountry = $validated['country'] ?? '';
        if (is_string($rawCountry) && strlen(trim($rawCountry)) === 2) {
            $c = strtoupper(trim($rawCountry));
        }

        $dn = [
            'CN' => $validated['common_name'],
            'C' => $c,
            'ST' => $validated['state'] ?? null,
            'L' => $validated['locality'] ?? null,
            'O' => $validated['organization'] ?? null,
            'OU' => $validated['organizational_unit'] ?? null,
            'emailAddress' => $validated['email'] ?? null,
        ];
        $dn = array_map(static fn ($v) => is_string($v) ? trim($v) : $v, $dn);
        $dn = array_filter(
            $dn,
            static fn ($v) => $v !== null && $v !== ''
        );
        if (! isset($dn['CN'])) {
            return $this->sslTlsErrorRedirect($hosting, 'csr', 'Common name (CN) is required.');
        }

        try {
            $csrPem = $ssl->generateCsr($validated['private_key'], $dn);
        } catch (Throwable $e) {
            return $this->sslTlsErrorRedirect($hosting, 'csr', $e->getMessage());
        }

        HostingSslStore::updateOrCreate(
            ['hosting_id' => $hosting->id],
            [
                'private_key_pem' => $validated['private_key'],
                'csr_pem' => $csrPem,
            ]
        );

        return redirect()
            ->route('hosts.ssl-tls', ['hosting' => $hosting, 'tab' => 'csr'])
            ->with('ssltls_csr_pem', $csrPem)
            ->with('ssltls_success', 'CSR generated and stored. The private key you used is saved encrypted for this host.');
    }

    private function sslTlsErrorRedirect(Hosting $hosting, string $tab, string $message): RedirectResponse
    {
        return redirect()
            ->route('hosts.ssl-tls', ['hosting' => $hosting, 'tab' => $tab])
            ->with('ssltls_error', $message);
    }

    private function resolveSslInstallDirectory(Hosting $hosting): string
    {
        $root = trim((string) ($hosting->host_root_path ?? ''));
        if ($root === '') {
            throw new RuntimeException('Host root path is not set for this hosting account.');
        }

        $sslDir = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'ssl';
        File::ensureDirectoryExists($sslDir);
        if (! is_writable($sslDir)) {
            throw new RuntimeException('SSL directory is not writable: '.$sslDir);
        }

        return $sslDir;
    }

    private function runNginxSslInstall(
        Hosting $hosting,
        string $sslDir,
        string $keyPath,
        string $fullchainPath
    ): string {
        $systemBin = (string) config('ssltls.nginx_ssl_system_install_bin', '');
        $script = (string) config('ssltls.nginx_ssl_install_script', '');
        $timeout = (float) config('ssltls.nginx_ssl_install_timeout', 90);

        $command = null;
        if ($systemBin !== '' && is_executable($systemBin)) {
            $command = ['sudo', '-n', $systemBin, $hosting->siteHost(), (string) $hosting->web_root_path, $keyPath, $fullchainPath];
        } elseif ($script !== '' && is_file($script)) {
            $command = ['bash', $script, $hosting->siteHost(), (string) $hosting->web_root_path, $keyPath, $fullchainPath];
        }

        if ($command === null) {
            throw new RuntimeException(
                'Nginx SSL installer is not configured. Set SSLTLS_NGINX_SSL_INSTALL_SCRIPT or SSLTLS_NGINX_SSL_SYSTEM_INSTALL_BIN.'
            );
        }

        $process = new Process($command, base_path(), [
            'SSL_DIR' => $sslDir,
            'PHP_FPM_SOCKET' => $this->resolvePhpFpmSocket($hosting),
        ], null, $timeout);
        $process->run();
        $out = trim($process->getOutput()."\n".$process->getErrorOutput());

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Nginx SSL activate failed: '.($out !== '' ? $out : 'no output'));
        }

        return $out !== '' ? $out : 'Nginx SSL vhost reloaded.';
    }

    private function resolvePhpFpmSocket(Hosting $hosting): string
    {
        $v = trim((string) $hosting->php_version);
        if (preg_match('/^(\d+)\.(\d+)/', $v, $m)) {
            return '/var/run/php/php'.$m[1].'.'.$m[2].'-fpm.sock';
        }

        return '/var/run/php/php8.3-fpm.sock';
    }
}
