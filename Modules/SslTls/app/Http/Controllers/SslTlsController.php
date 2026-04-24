<?php

namespace Modules\SslTls\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SslTls\Services\SslTlsOpenSslService;
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

        $sslSan = $hosting->ssl_san_hostnames;
        if (! is_array($sslSan)) {
            $sslSan = [];
        }

        return view('ssltls::index', [
            'hosting' => $hosting,
            'httpsUrl' => 'https://'.$host,
            'httpUrl' => 'http://'.$host,
            'activeToolTab' => $tab,
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
        $hosting->update(['ssl_san_hostnames' => array_values($san)]);

        return redirect()
            ->route('hosts.ssl-tls', ['hosting' => $hosting, 'tab' => 'hosts'])
            ->with('ssltls_success', 'SSL host names (SANs) saved for this hosting account. Use them as subject alternative names when requesting or installing a certificate.');
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

        return redirect()
            ->route('hosts.ssl-tls', ['hosting' => $hosting, 'tab' => 'key'])
            ->with('ssltls_key_pem', $pem)
            ->with('ssltls_success', 'Private key generated. Copy and store it securely; it is not saved by the panel.');
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

        return redirect()
            ->route('hosts.ssl-tls', ['hosting' => $hosting, 'tab' => 'csr'])
            ->with('ssltls_csr_pem', $csrPem)
            ->with('ssltls_success', 'CSR generated. Send this PEM to your certificate authority or use it with ACME.');
    }

    private function sslTlsErrorRedirect(Hosting $hosting, string $tab, string $message): RedirectResponse
    {
        return redirect()
            ->route('hosts.ssl-tls', ['hosting' => $hosting, 'tab' => $tab])
            ->with('ssltls_error', $message);
    }
}
