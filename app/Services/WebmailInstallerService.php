<?php

namespace App\Services;

use App\Support\ModuleSettings;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class WebmailInstallerService
{
    private const FALLBACK_WEBROOT_DIRNAME = 'roundcube-central-public';

    public function installRoundcubeFromArchive(): array
    {
        $archivePath = public_path('roundcubemail-1.5.15-complete.tar.gz');
        if (! is_file($archivePath)) {
            return [
                'ok' => false,
                'error' => 'Archive not found: public/roundcubemail-1.5.15-complete.tar.gz',
            ];
        }

        $existingTarget = $this->detectInstalledRoundcubeRoot();
        if ($existingTarget !== null && is_file($existingTarget.DIRECTORY_SEPARATOR.'index.php')) {
            return [
                'ok' => true,
                'message' => 'Roundcube is already installed at '.$existingTarget.'.',
            ];
        }

        $extractDir = $this->resolveWritableDirectory([
            storage_path('app/roundcube-central-install'),
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'xenweet-roundcube-central-install',
        ]);
        if (! $extractDir['ok']) {
            return $extractDir;
        }
        $extractBase = (string) $extractDir['path'];

        try {
            $process = new Process(['tar', '-xzf', $archivePath, '-C', $extractBase]);
            $process->setTimeout(180);
            $process->run();
            if (! $process->isSuccessful()) {
                throw new RuntimeException(trim($process->getErrorOutput()."\n".$process->getOutput()) ?: 'tar extract failed');
            }
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Could not extract Roundcube archive: '.$e->getMessage(),
            ];
        }

        $source = $extractBase.DIRECTORY_SEPARATOR.'roundcubemail-1.5.15';
        if (! is_file($source.DIRECTORY_SEPARATOR.'index.php')) {
            return [
                'ok' => false,
                'error' => 'Extracted archive does not contain roundcubemail-1.5.15/index.php',
            ];
        }

        $targetResolve = $this->resolveRoundcubeTargetRoot();
        if (! $targetResolve['ok']) {
            return $targetResolve;
        }
        $targetRoot = (string) $targetResolve['path'];

        try {
            if (is_dir($targetRoot)) {
                File::deleteDirectory($targetRoot);
            }
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Could not remove existing public/roundcube: '.$e->getMessage(),
            ];
        }

        try {
            if (! File::copyDirectory($source, $targetRoot)) {
                return [
                    'ok' => false,
                    'error' => 'Could not copy extracted Roundcube files to public/roundcube.',
                ];
            }
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Could not write to public/roundcube (permission issue): '.$e->getMessage(),
            ];
        }

        $bootstrap = $this->bootstrapCentralRoundcube($targetRoot);
        if (! $bootstrap['ok']) {
            return $bootstrap;
        }

        $centralHost = $this->centralWebmailHost();
        if ($centralHost === null) {
            return [
                'ok' => true,
                'message' => 'Roundcube installed at '.$targetRoot.'. Set WebMail central URL to auto-create nginx and AutoSSL.',
            ];
        }

        $nginx = $this->createAndActivateNginxRules($centralHost, $targetRoot);
        if (! $nginx['ok']) {
            return $nginx;
        }

        $ssl = $this->requestAutoSslForCentralHost($centralHost, $targetRoot);
        if (! $ssl['ok']) {
            return [
                'ok' => false,
                'error' => 'Nginx is ready, but AutoSSL failed: '.(string) ($ssl['error'] ?? 'unknown error'),
            ];
        }

        return [
            'ok' => true,
            'message' => 'Roundcube installed at '.$targetRoot.'. Nginx configured for '.$centralHost.' and AutoSSL completed.',
        ];
    }

    public function uninstallRoundcube(): array
    {
        $targetRoot = $this->detectInstalledRoundcubeRoot() ?? public_path('roundcube');
        $dbPath = storage_path('app/roundcube-central/roundcube.sqlite');
        $extractBase = storage_path('app/roundcube-central-install');
        $fallbackTarget = storage_path('app/'.self::FALLBACK_WEBROOT_DIRNAME);

        if (is_dir($targetRoot)) {
            File::deleteDirectory($targetRoot);
        }
        if ($targetRoot !== $fallbackTarget && is_dir($fallbackTarget)) {
            File::deleteDirectory($fallbackTarget);
        }
        if (is_file($dbPath)) {
            File::delete($dbPath);
        }
        if (is_dir($extractBase)) {
            File::deleteDirectory($extractBase);
        }

        $centralHost = $this->centralWebmailHost();
        if ($centralHost !== null) {
            $this->deactivateNginxRules($centralHost);
        }

        return [
            'ok' => true,
            'message' => 'Roundcube uninstalled. Files and central SQLite database were removed.',
        ];
    }

    public function installationStatus(): array
    {
        $root = $this->detectInstalledRoundcubeRoot();

        return [
            'installed' => $root !== null,
            'path' => $root,
        ];
    }

    private function bootstrapCentralRoundcube(string $targetRoot): array
    {
        $configDir = $targetRoot.DIRECTORY_SEPARATOR.'config';
        $tempDir = $targetRoot.DIRECTORY_SEPARATOR.'temp';
        $logsDir = $targetRoot.DIRECTORY_SEPARATOR.'logs';
        $dirCreate = $this->ensureDirectoriesSafe([$configDir, $tempDir, $logsDir], 'Roundcube runtime directories');
        if (! $dirCreate['ok']) {
            return $dirCreate;
        }

        $dbDirResult = $this->resolveWritableDirectory([
            storage_path('app/roundcube-central'),
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'xenweet-roundcube-central-db',
        ]);
        if (! $dbDirResult['ok']) {
            return $dbDirResult;
        }
        $dbDir = (string) $dbDirResult['path'];
        $dbPath = $dbDir.DIRECTORY_SEPARATOR.'roundcube.sqlite';

        $init = $this->initSqliteDbIfNeeded($targetRoot, $dbPath);
        if (! $init['ok']) {
            return $init;
        }

        $imapHost = trim((string) ModuleSettings::get('webmail_imap_host', 'localhost'));
        $smtpHost = trim((string) ModuleSettings::get('webmail_smtp_host', 'localhost'));
        $imapPort = (int) ModuleSettings::get('webmail_imap_port', 143);
        $smtpPort = (int) ModuleSettings::get('webmail_smtp_port', 587);
        $useTls = ModuleSettings::bool('webmail_use_tls', false);
        $defaultHost = $useTls ? 'tls://'.$imapHost : $imapHost;
        $smtpServer = $useTls ? 'tls://'.$smtpHost : $smtpHost;
        $desKey = substr(hash('sha256', 'xenweet|roundcube|central|'.$targetRoot), 0, 24);
        $escapedDbPath = str_replace('\\', '\\\\', $dbPath);

        $configPhp = <<<PHP
<?php

\$config = [];
\$config['db_dsnw'] = 'sqlite:///$escapedDbPath?mode=0640';
\$config['default_host'] = '$defaultHost';
\$config['default_port'] = $imapPort;
\$config['smtp_server'] = '$smtpServer';
\$config['smtp_port'] = $smtpPort;
\$config['smtp_user'] = '%u';
\$config['smtp_pass'] = '%p';
\$config['product_name'] = 'Webmail';
\$config['des_key'] = '$desKey';
\$config['plugins'] = ['archive', 'zipdownload'];
\$config['skin'] = 'elastic';
\$config['enable_installer'] = false;
\$config['temp_dir'] = __DIR__ . '/../temp';
\$config['log_dir'] = __DIR__ . '/../logs';
PHP;

        try {
            File::put($configDir.DIRECTORY_SEPARATOR.'config.inc.php', $configPhp);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Could not write Roundcube config file: '.$e->getMessage(),
            ];
        }

        return ['ok' => true];
    }

    private function initSqliteDbIfNeeded(string $targetRoot, string $dbPath): array
    {
        if (! class_exists(PDO::class)) {
            return ['ok' => false, 'error' => 'PDO extension is required for Roundcube database setup.'];
        }
        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            return ['ok' => false, 'error' => 'PDO SQLite driver is required (pdo_sqlite).'];
        }

        if (is_file($dbPath)) {
            return ['ok' => true];
        }

        try {
            File::put($dbPath, '');
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Could not create Roundcube SQLite DB file: '.$e->getMessage(),
            ];
        }
        $schemaPath = $targetRoot.DIRECTORY_SEPARATOR.'SQL'.DIRECTORY_SEPARATOR.'sqlite.initial.sql';
        if (! is_file($schemaPath)) {
            return ['ok' => false, 'error' => 'Roundcube schema missing: SQL/sqlite.initial.sql'];
        }

        try {
            $pdo = new PDO('sqlite:'.$dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $schema = (string) file_get_contents($schemaPath);
            if (trim($schema) === '') {
                throw new RuntimeException('SQLite schema file is empty.');
            }
            $pdo->exec($schema);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not initialize Roundcube SQLite DB: '.$e->getMessage()];
        }

        return ['ok' => true];
    }

    private function createAndActivateNginxRules(string $host, string $webRoot): array
    {
        if (! (bool) config('hosting_provision.vhost_enabled', false)) {
            return ['ok' => true];
        }

        $script = (string) config('hosting_provision.vhost_script', '');
        if ($script === '' || ! is_file($script)) {
            return ['ok' => false, 'error' => 'Nginx vhost script missing. Set HOSTING_VHOST_SCRIPT.'];
        }

        $outputDirResult = $this->resolveWritableDirectory([
            storage_path('app/hosting-vhosts'),
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'xenweet-hosting-vhosts',
        ]);
        if (! $outputDirResult['ok']) {
            return $outputDirResult;
        }
        $outputDir = (string) $outputDirResult['path'];

        $process = new Process(
            ['bash', $script, $host, $webRoot],
            base_path(),
            [
                'HOSTING_VHOST_OUTPUT_DIR' => $outputDir,
                'PHP_FPM_SOCKET' => '/var/run/php/php8.3-fpm.sock',
            ],
            null,
            (float) config('hosting_provision.timeout', 120)
        );
        $process->run();
        if (! $process->isSuccessful()) {
            return ['ok' => false, 'error' => 'Could not generate nginx vhost: '.trim($process->getErrorOutput()."\n".$process->getOutput())];
        }

        if (! (bool) config('hosting_provision.vhost_nginx_activate', false)) {
            return ['ok' => true];
        }

        $activateSystem = (string) config('hosting_provision.vhost_nginx_system_activate', '');
        $activateScript = (string) config('hosting_provision.vhost_nginx_activate_script', '');

        if ($activateSystem !== '' && is_executable($activateSystem)) {
            $activate = new Process(['sudo', '-n', $activateSystem, $host, $outputDir], base_path(), [], null, (float) config('hosting_provision.timeout', 120));
            $activate->run();
            if (! $activate->isSuccessful()) {
                return ['ok' => false, 'error' => 'Nginx activate failed: '.trim($activate->getErrorOutput()."\n".$activate->getOutput())];
            }

            return ['ok' => true];
        }

        if ($activateScript !== '' && is_file($activateScript)) {
            $activate = new Process(['bash', $activateScript, $host, $outputDir], base_path(), ['HOSTING_VHOST_OUTPUT_DIR' => $outputDir], null, (float) config('hosting_provision.timeout', 120));
            $activate->run();
            if (! $activate->isSuccessful()) {
                return ['ok' => false, 'error' => 'Nginx activate failed: '.trim($activate->getErrorOutput()."\n".$activate->getOutput())];
            }
        }

        return ['ok' => true];
    }

    private function requestAutoSslForCentralHost(string $host, string $webRoot): array
    {
        if (! (bool) config('ssltls.letsencrypt_enabled', false)) {
            return ['ok' => true];
        }

        $email = trim((string) config('ssltls.letsencrypt_email', ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Set SSLTLS_LETSENCRYPT_EMAIL before AutoSSL.'];
        }

        $bin = (string) config('ssltls.letsencrypt_certbot', 'certbot');
        if ($bin === '' || (! str_starts_with($bin, '/') && $bin !== 'certbot')) {
            $bin = 'certbot';
        }
        $useSudo = (bool) config('ssltls.letsencrypt_use_sudo', true);
        $certName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $host) ?: 'xenweet-webmail';
        $cmd = array_merge(
            $useSudo ? ['sudo', '-n'] : [],
            [
                $bin, 'certonly',
                '--webroot',
                '-w', $webRoot,
                '-d', $host,
                '--email', $email,
                '--agree-tos',
                '--non-interactive',
                '--no-eff-email',
                '--expand',
                '--cert-name', $certName,
                '--config-dir', (string) config('ssltls.letsencrypt_config_dir', '/etc/letsencrypt'),
                '--work-dir', (string) config('ssltls.letsencrypt_work_dir', '/var/lib/letsencrypt'),
                '--logs-dir', (string) config('ssltls.letsencrypt_logs_dir', '/var/log/letsencrypt'),
            ]
        );
        if ((bool) config('ssltls.letsencrypt_staging', false)) {
            $cmd[] = '--staging';
        }

        $process = new Process($cmd, base_path(), ['LE_WEBROOT' => $webRoot], null, (float) config('ssltls.letsencrypt_timeout', 300));
        $process->run();
        if (! $process->isSuccessful()) {
            return ['ok' => false, 'error' => trim($process->getErrorOutput()."\n".$process->getOutput()) ?: 'certbot failed'];
        }

        $liveBase = rtrim((string) config('ssltls.letsencrypt_config_dir', '/etc/letsencrypt'), '/').DIRECTORY_SEPARATOR.'live'.DIRECTORY_SEPARATOR.$certName;
        $keyPath = $liveBase.DIRECTORY_SEPARATOR.'privkey.pem';
        $fullchainPath = $liveBase.DIRECTORY_SEPARATOR.'fullchain.pem';

        $sslInstall = $this->installNginxSslForCentralHost($host, $webRoot, $keyPath, $fullchainPath);
        if (! $sslInstall['ok']) {
            return $sslInstall;
        }

        return ['ok' => true];
    }

    private function installNginxSslForCentralHost(string $host, string $webRoot, string $keyPath, string $fullchainPath): array
    {
        $systemBin = (string) config('ssltls.nginx_ssl_system_install_bin', '');
        if ($systemBin !== '' && is_executable($systemBin)) {
            $p = new Process(
                ['sudo', '-n', $systemBin, $host, $webRoot, $keyPath, $fullchainPath],
                base_path(),
                [],
                null,
                (float) config('ssltls.nginx_ssl_install_timeout', 90)
            );
            $p->run();
            if (! $p->isSuccessful()) {
                return ['ok' => false, 'error' => 'AutoSSL issued cert but nginx SSL install failed: '.trim($p->getErrorOutput()."\n".$p->getOutput())];
            }

            return ['ok' => true];
        }

        $script = (string) config('ssltls.nginx_ssl_install_script', '');
        if ($script === '' || ! is_file($script)) {
            return ['ok' => false, 'error' => 'AutoSSL issued cert, but SSL install script is missing (SSLTLS_NGINX_SSL_INSTALL_SCRIPT).'];
        }

        $p = new Process(
            ['bash', $script, $host, $webRoot, $keyPath, $fullchainPath],
            base_path(),
            [],
            null,
            (float) config('ssltls.nginx_ssl_install_timeout', 90)
        );
        $p->run();
        if (! $p->isSuccessful()) {
            return ['ok' => false, 'error' => 'AutoSSL issued cert but nginx SSL install failed: '.trim($p->getErrorOutput()."\n".$p->getOutput())];
        }

        return ['ok' => true];
    }

    private function centralWebmailHost(): ?string
    {
        $url = trim((string) ModuleSettings::get('webmail_central_url', ''));
        if ($url === '') {
            return null;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || trim($host) === '') {
            return null;
        }

        return strtolower(trim($host));
    }

    private function deactivateNginxRules(string $host): void
    {
        if (! (bool) config('hosting_provision.vhost_nginx_activate', false)) {
            return;
        }

        $systemDeactivate = (string) config('hosting_provision.vhost_nginx_system_deactivate', '');
        $scriptDeactivate = (string) config('hosting_provision.vhost_nginx_deactivate_script', '');

        if ($systemDeactivate !== '' && is_executable($systemDeactivate)) {
            $p = new Process(['sudo', '-n', $systemDeactivate, $host], base_path(), [], null, (float) config('hosting_provision.timeout', 120));
            $p->run();

            return;
        }

        if ($scriptDeactivate !== '' && is_file($scriptDeactivate)) {
            $p = new Process(['bash', $scriptDeactivate, $host], base_path(), [], null, (float) config('hosting_provision.timeout', 120));
            $p->run();
        }
    }

    private function resolveWritableDirectory(array $candidates): array
    {
        foreach ($candidates as $path) {
            $dir = trim((string) $path);
            if ($dir === '') {
                continue;
            }

            try {
                File::ensureDirectoryExists($dir);
            } catch (Throwable) {
                continue;
            }

            if (is_dir($dir) && is_writable($dir)) {
                return ['ok' => true, 'path' => $dir];
            }
        }

        return [
            'ok' => false,
            'error' => 'Permission denied while creating installer directories. Ensure PHP can write to storage/ (or system temp directory).',
        ];
    }

    private function resolveRoundcubeTargetRoot(): array
    {
        $publicTarget = public_path('roundcube');
        if ($this->isPathWritableOrCreatable($publicTarget)) {
            return ['ok' => true, 'path' => $publicTarget];
        }

        $fallback = $this->resolveWritableDirectory([
            storage_path('app/'.self::FALLBACK_WEBROOT_DIRNAME),
            sys_get_temp_dir().DIRECTORY_SEPARATOR.self::FALLBACK_WEBROOT_DIRNAME,
        ]);
        if (! $fallback['ok']) {
            return [
                'ok' => false,
                'error' => 'public/roundcube is not writable and fallback webroot directory could not be created.',
            ];
        }

        return [
            'ok' => true,
            'path' => (string) $fallback['path'],
        ];
    }

    private function detectInstalledRoundcubeRoot(): ?string
    {
        $publicTarget = public_path('roundcube');
        if (is_file($publicTarget.DIRECTORY_SEPARATOR.'index.php')) {
            return $publicTarget;
        }

        $fallbackTarget = storage_path('app/'.self::FALLBACK_WEBROOT_DIRNAME);
        if (is_file($fallbackTarget.DIRECTORY_SEPARATOR.'index.php')) {
            return $fallbackTarget;
        }

        $tmpFallback = sys_get_temp_dir().DIRECTORY_SEPARATOR.self::FALLBACK_WEBROOT_DIRNAME;
        if (is_file($tmpFallback.DIRECTORY_SEPARATOR.'index.php')) {
            return $tmpFallback;
        }

        return null;
    }

    private function isPathWritableOrCreatable(string $path): bool
    {
        if (is_dir($path)) {
            return is_writable($path);
        }

        $parent = dirname($path);
        if (! is_dir($parent)) {
            return false;
        }

        return is_writable($parent);
    }

    private function ensureDirectoriesSafe(array $paths, string $label): array
    {
        foreach ($paths as $path) {
            $dir = trim((string) $path);
            if ($dir === '') {
                continue;
            }
            try {
                File::ensureDirectoryExists($dir);
            } catch (Throwable $e) {
                return [
                    'ok' => false,
                    'error' => 'Could not create '.$label.' at '.$dir.': '.$e->getMessage(),
                ];
            }
        }

        return ['ok' => true];
    }
}
