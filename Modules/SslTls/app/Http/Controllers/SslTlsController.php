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

        $certRaw = trim((string) ($validated['certificate_pem'] ?? ''));
        $chainRaw = trim((string) ($validated['certificate_chain_pem'] ?? ''));

        if ($certRaw !== '') {
            $certBlocks = $this->extractCertificatePemBlocks($certRaw);
            if ($certBlocks === []) {
                return $this->sslTlsErrorRedirect(
                    $hosting,
                    'cert',
                    'Leaf certificate is not valid PEM. Paste a full block from -----BEGIN CERTIFICATE----- to -----END CERTIFICATE-----.'
                );
            }
            if (! $this->areValidX509PemBlocks($certBlocks)) {
                return $this->sslTlsErrorRedirect(
                    $hosting,
                    'cert',
                    'Leaf certificate format is invalid. Use an X.509 PEM certificate (.crt/.pem), not PKCS#7 (.p7b) or non-certificate data.'
                );
            }
            $cert = implode("\n", $certBlocks)."\n";
        } else {
            $cert = null;
        }

        if ($chainRaw !== '') {
            $chainBlocks = $this->extractCertificatePemBlocks($chainRaw);
            if ($chainBlocks === []) {
                return $this->sslTlsErrorRedirect(
                    $hosting,
                    'cert',
                    'Certificate chain is not valid PEM. Paste one or more full CERTIFICATE blocks.'
                );
            }
            if (! $this->areValidX509PemBlocks($chainBlocks)) {
                return $this->sslTlsErrorRedirect(
                    $hosting,
                    'cert',
                    'Certificate chain contains invalid certificate data. Use PEM X.509 intermediate certificates only.'
                );
            }
            $chain = implode("\n", $chainBlocks)."\n";
        } else {
            $chain = null;
        }

        HostingSslStore::updateOrCreate(
            ['hosting_id' => $hosting->id],
            [
                'certificate_pem' => $cert,
                'certificate_chain_pem' => $chain,
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
        $leafBlocks = $this->extractCertificatePemBlocks($certificate);
        if ($leafBlocks === []) {
            return $this->sslTlsErrorRedirect($hosting, 'cert', 'Saved certificate is missing or invalid.');
        }
        if (! $this->areValidX509PemBlocks($leafBlocks)) {
            return $this->sslTlsErrorRedirect(
                $hosting,
                'cert',
                'Saved certificate is not a valid X.509 PEM certificate. Save a valid leaf certificate and try install again.'
            );
        }
        $chainBlocks = $chain === '' ? [] : $this->extractCertificatePemBlocks($chain);
        if ($chain !== '' && $chainBlocks === []) {
            return $this->sslTlsErrorRedirect($hosting, 'cert', 'Saved certificate chain is invalid PEM.');
        }
        if ($chainBlocks !== [] && ! $this->areValidX509PemBlocks($chainBlocks)) {
            return $this->sslTlsErrorRedirect(
                $hosting,
                'cert',
                'Saved certificate chain contains invalid X.509 certificate data.'
            );
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

            File::put($certPath, implode("\n", $leafBlocks)."\n");
            @chmod($certPath, 0644);

            $fullchain = implode("\n", $leafBlocks)."\n";
            if ($chainBlocks !== []) {
                File::put($chainPath, implode("\n", $chainBlocks)."\n");
                @chmod($chainPath, 0644);
                $fullchain .= implode("\n", $chainBlocks)."\n";
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

    public function downloadCsr(Hosting $hosting)
    {
        $csr = trim((string) ($hosting->sslStore?->csr_pem ?? ''));
        if ($csr === '' || ! str_contains($csr, 'CERTIFICATE REQUEST')) {
            return $this->sslTlsErrorRedirect($hosting, 'csr', 'No stored CSR found. Generate CSR first.');
        }

        $filename = preg_replace('/[^a-zA-Z0-9.\-_]/', '-', $hosting->siteHost()).'.csr.pem';

        return response()->streamDownload(
            static function () use ($csr): void {
                echo $csr."\n";
            },
            $filename,
            ['Content-Type' => 'application/x-pem-file; charset=UTF-8']
        );
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
            if (str_contains(strtolower($out), 'a password is required')) {
                throw new RuntimeException(
                    'Nginx SSL activate failed: sudo requires password. Run once on server: bash scripts/install-xenweet-nginx-sudo.sh www-data'
                );
            }
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

    /**
     * @return list<string>
     */
    private function extractCertificatePemBlocks(string $pem): array
    {
        $input = trim($pem);
        if ($input === '') {
            return [];
        }

        // Some providers/API payloads include literal "\n" instead of real line breaks.
        if (! str_contains($input, "\n") && str_contains($input, '\n')) {
            $input = str_replace('\n', "\n", $input);
        }

        $matches = [];
        preg_match_all(
            '/-----BEGIN (?:TRUSTED )?CERTIFICATE-----\s*[\s\S]+?\s*-----END (?:TRUSTED )?CERTIFICATE-----/m',
            $input,
            $matches
        );
        $blocks = [];
        foreach (($matches[0] ?? []) as $raw) {
            $b = trim((string) $raw);
            if ($b !== '') {
                // Normalize TRUSTED CERTIFICATE wrappers to CERTIFICATE for broad compatibility.
                $b = str_replace('BEGIN TRUSTED CERTIFICATE', 'BEGIN CERTIFICATE', $b);
                $b = str_replace('END TRUSTED CERTIFICATE', 'END CERTIFICATE', $b);
                $blocks[] = $b;
            }
        }

        return $blocks;
    }

    /**
     * @param  list<string>  $blocks
     */
    private function areValidX509PemBlocks(array $blocks): bool
    {
        foreach ($blocks as $block) {
            $x = @openssl_x509_read($block);
            if ($x === false) {
                return false;
            }
            openssl_x509_free($x);
        }

        return true;
    }
}
