<?php

namespace Modules\SslTls\Services;

use App\Models\Hosting;
use App\Models\HostingSslStore;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class SslTlsLetsEncryptService
{
    public function __construct(
        private readonly SslTlsNginxPemService $pems
    ) {}

    /**
     * Issue a certificate with certbot (webroot), read PEMs from /etc/letsencrypt/live, materialize, update store.
     *
     * @return string Success message (certbot + nginx output summary)
     */
    public function issueAndInstallAutoSsl(Hosting $hosting): string
    {
        if (! (bool) config('ssltls.letsencrypt_enabled', false)) {
            throw new RuntimeException("Let's Encrypt (Auto SSL) is disabled. Set SSLTLS_LETSENCRYPT_ENABLED=true in the panel .env.");
        }

        $webRoot = trim((string) ($hosting->web_root_path ?? ''));
        if ($webRoot === '' || ! is_dir($webRoot)) {
            throw new RuntimeException('Web root path is missing or not a directory. Set a valid public document root (e.g. site public/).');
        }

        $email = trim((string) config('ssltls.letsencrypt_email', ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Let's Encrypt account email is required. Set SSLTLS_LETSENCRYPT_EMAIL in .env.");
        }

        $primary = $hosting->siteHost();
        if ($primary === '') {
            throw new RuntimeException('Hosting domain is not set.');
        }

        $domains = $this->buildDomainList($hosting, $primary);
        if ($domains === []) {
            throw new RuntimeException('No hostnames to request.');
        }

        $command = $this->buildCertbotCommand($webRoot, $primary, $domains, (bool) config('ssltls.letsencrypt_staging', false), $email);
        $timeout = (float) config('ssltls.letsencrypt_timeout', 300);

        $process = new Process(
            $command,
            base_path(),
            $this->buildCertbotEnv($webRoot),
            null,
            $timeout
        );
        $process->run();
        $combined = trim($process->getOutput()."\n".$process->getErrorOutput());
        if (! $process->isSuccessful()) {
            $this->throwCertbotProcessFailed($combined, (bool) config('ssltls.letsencrypt_use_sudo', true));
        }

        $liveName = $this->resolveCertbotLiveName($hosting, $primary);
        $privPath = $this->letsEncryptLivePath($liveName, 'privkey.pem');
        $fullchainPath = $this->letsEncryptLivePath($liveName, 'fullchain.pem');
        if (! is_readable($privPath) || ! is_readable($fullchainPath)) {
            throw new RuntimeException('Certbot reported success but PEM files are missing: '.$fullchainPath);
        }
        $keyPem = (string) file_get_contents($privPath);
        $fullchainPem = (string) file_get_contents($fullchainPath);
        if (trim($keyPem) === '' || trim($fullchainPem) === '') {
            throw new RuntimeException('Read empty PEM from Let\'s Encrypt live directory.');
        }

        try {
            [$leafPem, $chainBlockList] = $this->pems->splitFullchainPemToLeafAndChain($fullchainPem);
            $chainPem = $chainBlockList === [] ? null : (implode("\n", $chainBlockList)."\n");
        } catch (Throwable $e) {
            throw new RuntimeException('Could not parse issued certificate chain: '.$e->getMessage());
        }

        $nginxMsg = $this->pems->materializePemToDiskAndReloadNginx(
            $hosting,
            $keyPem,
            [$leafPem],
            $chainBlockList
        );

        $storeUpdate = [
            'private_key_pem' => $keyPem,
            'key_type' => 'letsencrypt',
            'csr_pem' => null,
            'certificate_pem' => $leafPem,
            'certificate_chain_pem' => $chainPem,
            'letsencrypt_issued_at' => now(),
            'letsencrypt_staging' => (bool) config('ssltls.letsencrypt_staging', false),
        ];
        HostingSslStore::updateOrCreate(
            ['hosting_id' => $hosting->id],
            $storeUpdate
        );

        $line = 'Let\'s Encrypt issued and installed for: '.implode(', ', $domains).'.';
        if ($combined !== '' && strlen($combined) < 500) {
            $line .= ' '.trim($combined);
        }

        return rtrim($line.' '.$nginxMsg);
    }

    /**
     * Re-read /etc/letsencrypt/live and reinstall nginx (after `certbot renew` from cron).
     */
    public function resyncFromLiveDirectory(Hosting $hosting): string
    {
        $store = $hosting->sslStore;
        if (! $store?->letsencrypt_issued_at) {
            return 'Skipped: this host was not issued via panel Auto SSL.';
        }
        $primary = $hosting->siteHost();
        $liveName = $this->resolveCertbotLiveName($hosting, $primary);
        $privPath = $this->letsEncryptLivePath($liveName, 'privkey.pem');
        $fullPath = $this->letsEncryptLivePath($liveName, 'fullchain.pem');
        if (! is_readable($privPath) || ! is_readable($fullPath)) {
            throw new RuntimeException('No Let\'s Encrypt files in live directory for '.$liveName);
        }
        $keyPem = (string) file_get_contents($privPath);
        $fullchainPem = (string) file_get_contents($fullPath);
        [$leafPem, $chainBlockList] = $this->pems->splitFullchainPemToLeafAndChain($fullchainPem);
        $chainPem = $chainBlockList === [] ? null : (implode("\n", $chainBlockList)."\n");

        $msg = $this->pems->materializePemToDiskAndReloadNginx(
            $hosting,
            $keyPem,
            [$leafPem],
            $chainBlockList
        );
        $store->update([
            'private_key_pem' => $keyPem,
            'certificate_pem' => $leafPem,
            'certificate_chain_pem' => $chainPem,
        ]);

        return 'Resynced from '.$this->letsEncryptRoot().'/live/'.$liveName.'. '.$msg;
    }

    /**
     * @return list<string> domain names, primary first, unique
     */
    public function buildDomainList(Hosting $hosting, string $primary): array
    {
        $seen = [mb_strtolower($primary) => true];
        $out = [$primary];
        $extra = is_array($hosting->sslStore?->san_hostnames) ? $hosting->sslStore->san_hostnames : [];
        foreach ($extra as $h) {
            if (! is_string($h) || $h === '') {
                continue;
            }
            $n = Hosting::normalizeDomainName($h);
            if ($n === '' || ! preg_match('/^[a-zA-Z0-9.\-]+$/', $n)) {
                continue;
            }
            $k = mb_strtolower($n);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = $n;
        }

        if (count($out) > 100) {
            throw new RuntimeException('Too many hostnames for one certificate (max 100).');
        }

        return $out;
    }

    public function runCertbotRenewProcess(): void
    {
        $useSudo = (bool) config('ssltls.letsencrypt_use_sudo', true);
        $bin = (string) config('ssltls.letsencrypt_certbot', 'certbot');
        if ($bin === '' || (! str_starts_with($bin, '/') && $bin !== 'certbot')) {
            $bin = 'certbot';
        }
        $timeout = (float) config('ssltls.letsencrypt_renew_timeout', 600);
        $cmd = array_merge(
            $useSudo ? ['sudo', '-n'] : [],
            [$bin, 'renew', '--non-interactive', '--no-random-sleep-on-renew'],
        );
        $p = new Process($cmd, base_path(), null, null, $timeout);
        $p->run();
        if (! $p->isSuccessful()) {
            $out = trim($p->getErrorOutput()."\n".$p->getOutput());
            $this->throwCertbotProcessFailed($out, $useSudo);
        }
    }

    private function throwCertbotProcessFailed(string $combined, bool $usedSudo): void
    {
        $lower = strtolower($combined);
        $detail = $combined !== '' ? $combined : 'no output';

        if (
            $usedSudo
            && (
                str_contains($lower, 'a password is required')
                || str_contains($lower, 'password is required')
                || str_contains($lower, 'no tty present')
            )
        ) {
            $script = base_path('scripts/install-xenweet-certbot-sudo.sh');
            throw new RuntimeException(
                "Let's Encrypt (certbot) needs passwordless sudo for the PHP user (e.g. www-data). ".
                "On the server, run once as root: sudo bash {$script} www-data (replace www-data with your PHP-FPM user). ".
                "Details: {$detail}"
            );
        }

        $hint = 'Certbot must run on the app server. Ensure certbot is installed, DNS points here, and HTTP port 80 serves /.well-known/acme-challenge/ from the web root.';

        throw new RuntimeException(
            'Let\'s Encrypt (certbot) failed: '.$detail.'. '.$hint
        );
    }

    private function buildCertbotCommand(string $webRoot, string $primary, array $domains, bool $staging, string $email): array
    {
        $bin = (string) config('ssltls.letsencrypt_certbot', 'certbot');
        if ($bin === '' || (! str_starts_with($bin, '/') && $bin !== 'certbot')) {
            $bin = 'certbot';
        }
        $useSudo = (bool) config('ssltls.letsencrypt_use_sudo', true);
        if (str_starts_with($bin, '/') && $this->certbotPathMustExist() && ! is_executable($bin)) {
            throw new RuntimeException('certbot binary is not executable: '.$bin);
        }

        $configDir = (string) config('ssltls.letsencrypt_config_dir', '/etc/letsencrypt');
        $workDir = (string) config('ssltls.letsencrypt_work_dir', '/var/lib/letsencrypt');
        $logsDir = (string) config('ssltls.letsencrypt_logs_dir', '/var/log/letsencrypt');
        $hook = trim((string) config('ssltls.letsencrypt_pre_hook', ''));
        $args = array_merge(
            $useSudo ? ['sudo', '-n'] : [],
            [
                $bin, 'certonly',
                '--webroot',
                '-w', $webRoot,
            ]
        );
        foreach ($domains as $d) {
            $args[] = '-d';
            $args[] = $d;
        }
        $args = array_merge($args, [
            '--email', $email,
            '--agree-tos',
            '--non-interactive',
            '--no-eff-email',
            '--cert-name', $this->safeCertName($primary),
            '--config-dir', $configDir,
            '--work-dir', $workDir,
            '--logs-dir', $logsDir,
        ]);
        if ($hook !== '') {
            $args[] = '--pre-hook';
            $args[] = $hook;
        }
        if ($staging) {
            $args[] = '--staging';
        }
        $args[] = '--expand';

        return $args;
    }

    /**
     * @return array<string, string>
     */
    private function buildCertbotEnv(string $webRoot): array
    {
        return [
            'LE_WEBROOT' => $webRoot,
        ];
    }

    private function certbotPathMustExist(): bool
    {
        return (bool) config('ssltls.letsencrypt_check_binary', true);
    }

    private function safeCertName(string $primary): string
    {
        $s = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $primary) ?? $primary;
        if ($s === '' || $s === '-') {
            return 'xenweet-cert';
        }

        return $s;
    }

    private function resolveCertbotLiveName(Hosting $hosting, string $primary): string
    {
        $configDir = rtrim((string) config('ssltls.letsencrypt_config_dir', '/etc/letsencrypt'), '/');
        $certName = $this->safeCertName($primary);
        if (is_readable($configDir.'/live/'.$certName.'/fullchain.pem')) {
            return $certName;
        }
        if (is_readable($configDir.'/live/'.$primary.'/fullchain.pem')) {
            return $primary;
        }

        return $certName;
    }

    public function letsEncryptRoot(): string
    {
        return rtrim((string) config('ssltls.letsencrypt_config_dir', '/etc/letsencrypt'), '/');
    }

    public function letsEncryptLivePath(string $liveName, string $file): string
    {
        return $this->letsEncryptRoot().'/live/'.$liveName.'/'.$file;
    }
}
