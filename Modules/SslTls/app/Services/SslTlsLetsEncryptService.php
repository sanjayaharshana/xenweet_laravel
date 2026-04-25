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

        $liveName = $this->resolveCertbotLiveName($primary, $this->buildDomainList($hosting, $primary));

        $keyPem = $this->readPemFromLiveStorage($liveName, 'privkey');
        $fullchainPem = $this->readPemFromLiveStorage($liveName, 'fullchain');
        if (trim($keyPem) === '' || trim($fullchainPem) === '') {
            throw new RuntimeException('Read empty PEM from Let\'s Encrypt live directory.');
        }

        try {
            [$leafPem, $chainBlockList] = $this->pems->splitFullchainPemToLeafAndChain($fullchainPem);
            $chainPem = $chainBlockList === [] ? null : (implode("\n", $chainBlockList)."\n");
        } catch (Throwable $e) {
            throw new RuntimeException('Could not parse issued certificate chain: '.$e->getMessage());
        }

        // Same as manual "Install certificate": Nginx vhost (HTTPS) with correct PHP-FPM pool — see
        // SslTlsNginxPemService::runNginxSslInstall (5th arg for sudo helpers; env is not forwarded by sudo).
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
        $liveName = $this->resolveCertbotLiveName($primary, $this->buildDomainList($hosting, $primary));
        $keyPem = $this->readPemFromLiveStorage($liveName, 'privkey');
        $fullchainPem = $this->readPemFromLiveStorage($liveName, 'fullchain');
        [$leafPem, $chainBlockList] = $this->pems->splitFullchainPemToLeafAndChain($fullchainPem);
        $chainPem = $chainBlockList === [] ? null : (implode("\n", $chainBlockList)."\n");

        // Reapply Nginx vhost (HTTPS + same PHP-FPM args as initial Auto SSL) after certbot renew.
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

    /**
     * Name of the directory under live/ (matches certbot "Certificate Name"). The web user often
     * cannot stat() that directory (root-only 0700), so we use `certbot certificates` to discover it.
     *
     * @param  list<string>  $domainList
     */
    private function resolveCertbotLiveName(string $primary, array $domainList): string
    {
        $configDir = rtrim((string) config('ssltls.letsencrypt_config_dir', '/etc/letsencrypt'), '/');
        $candidates = array_unique(
            array_filter(
                [
                    $this->safeCertName($primary),
                    $primary,
                ],
                static fn (string $s): bool => $s !== ''
            )
        );

        $fromCertbot = $this->discoverLiveNameFromCertbotCertificates($configDir, $primary, $domainList);
        if (is_string($fromCertbot) && $fromCertbot !== '') {
            return $fromCertbot;
        }

        foreach ($candidates as $name) {
            if (is_dir($configDir.'/live/'.$name)) {
                return $name;
            }
        }

        return $this->safeCertName($primary);
    }

    /**
     * @param  list<string>  $domainList
     */
    private function discoverLiveNameFromCertbotCertificates(string $configDir, string $primary, array $domainList): ?string
    {
        $workDir = (string) config('ssltls.letsencrypt_work_dir', '/var/lib/letsencrypt');
        $logsDir = (string) config('ssltls.letsencrypt_logs_dir', '/var/log/letsencrypt');
        $bin = (string) config('ssltls.letsencrypt_certbot', 'certbot');
        if ($bin === '' || (! str_starts_with($bin, '/') && $bin !== 'certbot')) {
            $bin = 'certbot';
        }
        $useSudo = (bool) config('ssltls.letsencrypt_use_sudo', true);

        $args = array_merge(
            $useSudo ? ['sudo', '-n'] : [],
            [
                $bin,
                'certificates',
                '--config-dir',
                $configDir,
                '--work-dir',
                $workDir,
                '--logs-dir',
                $logsDir,
            ]
        );
        $process = new Process($args, base_path(), null, null, 30.0);
        $process->run();
        if (! $process->isSuccessful()) {
            return null;
        }

        $out = $process->getOutput();
        if (trim($out) === '') {
            return null;
        }

        $primaryLower = mb_strtolower($primary);
        $domainSet = [];
        foreach ($domainList as $d) {
            if (is_string($d) && $d !== '') {
                $domainSet[mb_strtolower($d)] = true;
            }
        }
        if ($primaryLower !== '') {
            $domainSet[$primaryLower] = true;
        }

        $lines = preg_split("/\R/", $out) ?: [];
        $currentName = null;
        foreach ($lines as $line) {
            if (preg_match('/^\s*Certificate Name:\s*(.+?)\s*$/i', $line, $m)) {
                $currentName = trim($m[1]);

                continue;
            }
            if ($currentName !== null && preg_match('/^\s*Domains:\s*(.+?)\s*$/i', $line, $m2)) {
                $domainsLine = $m2[1];
                $parts = preg_split('/[\s,]+/', $domainsLine, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                foreach ($parts as $d) {
                    if (isset($domainSet[mb_strtolower($d)])) {
                        return $currentName;
                    }
                }
            }
        }

        $candidates = array_unique(
            array_filter(
                [
                    $this->safeCertName($primary),
                    $primary,
                ],
                static fn (string $s): bool => $s !== ''
            )
        );
        foreach ($candidates as $c) {
            if (preg_match('/^\s*Certificate Name:\s*'.preg_quote($c, '/').'\s*$/im', $out)) {
                return $c;
            }
        }

        return null;
    }

    /**
     * Read a PEM from live storage. Files are often root-owned; privkey is typically mode 600, so
     * we fall back to sudo + xenweet-letsencrypt-read-pem (see install-xenweet-certbot-sudo.sh).
     *
     * @param  'privkey'|'fullchain'  $which
     */
    private function readPemFromLiveStorage(string $liveName, string $which): string
    {
        if (! in_array($which, ['privkey', 'fullchain'], true)) {
            throw new RuntimeException('Invalid PEM kind requested.');
        }

        $configDir = rtrim($this->letsEncryptRoot(), '/');
        $file = $which === 'privkey' ? 'privkey.pem' : 'fullchain.pem';
        $path = $configDir.'/live/'.$liveName.'/'.$file;
        if (is_readable($path)) {
            $content = (string) file_get_contents($path);
            if (trim($content) !== '') {
                return $content;
            }
        }
        if (! (bool) config('ssltls.letsencrypt_use_sudo', true)) {
            throw new RuntimeException(
                'Cannot read '.$path.' (not readable as the PHP user). '.
                'Set SSLTLS_LETSENCRYPT_USE_SUDO=true and run sudo bash '.base_path('scripts/install-xenweet-certbot-sudo.sh').' www-data, or adjust file permissions/ownership.'
            );
        }

        $installed = (string) config('ssltls.letsencrypt_read_pem', '/usr/local/sbin/xenweet-letsencrypt-read-pem');
        $fromRepo = base_path('scripts/xenweet-letsencrypt-read-pem.sh');

        if (is_executable($installed)) {
            $command = array_merge(
                ['sudo', '-n', $installed],
                [$configDir, $liveName, $which]
            );
        } elseif (is_file($fromRepo)) {
            $command = array_merge(
                ['sudo', '-n', 'bash', $fromRepo],
                [$configDir, $liveName, $which]
            );
        } else {
            throw new RuntimeException(
                'PEM read helper is not installed. Run as root: sudo bash '.base_path('scripts/install-xenweet-certbot-sudo.sh').' www-data'
            );
        }

        $process = new Process($command, base_path(), null, null, 30.0);
        $process->run();
        $out = $process->getOutput();
        $err = trim($process->getErrorOutput()."\n".$process->getOutput());
        if (! $process->isSuccessful()) {
            if (str_contains(strtolower($err), 'a password is required') || str_contains(strtolower($err), 'password is required')) {
                throw new RuntimeException(
                    'Cannot read '.$path.' with sudo (password required). Re-run: sudo bash '.base_path('scripts/install-xenweet-certbot-sudo.sh').' www-data'
                );
            }

            throw new RuntimeException(
                'Could not read '.$path.' (certbot data is usually root-only). '.
                'Re-run: sudo bash '.base_path('scripts/install-xenweet-certbot-sudo.sh').' www-data — '.$err
            );
        }
        if (trim($out) === '') {
            throw new RuntimeException('Read empty content for '.$path);
        }

        return $out;
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
