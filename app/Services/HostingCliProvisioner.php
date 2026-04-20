<?php

namespace App\Services;

use App\Models\Hosting;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class HostingCliProvisioner
{
    public function run(Hosting $hosting): void
    {
        [$hostRootPath, $webRootPath, $folderNote] = $this->ensureHostFolders($hosting);

        $hosting->update([
            'host_root_path' => $hostRootPath,
            'web_root_path' => $webRootPath,
        ]);
        $hosting->refresh();

        $vhostResult = $this->runVhostProvision($hosting);
        if ($vhostResult['stopped']) {
            $failLog = $vhostResult['message'] ?? 'Vhost script failed.';
            if ($folderNote !== null) {
                $failLog = $folderNote."\n".$failLog;
            }
            $hosting->update([
                'provision_status' => 'failed',
                'provision_log' => $failLog,
            ]);

            return;
        }
        $vhostLog = $vhostResult['message'];

        if (! config('hosting_provision.enabled')) {
            $log = 'Provision command disabled. Folder binding created at: '.$webRootPath;
            if ($folderNote !== null) {
                $log .= "\n".$folderNote;
            }
            if ($vhostLog !== null) {
                $log .= "\n".$vhostLog;
            }
            $hosting->update([
                'provision_status' => 'success',
                'provision_log' => $log,
                'provisioned_at' => Carbon::now(),
            ]);

            return;
        }

        $command = $this->applyHostingPlaceholders($hosting, (string) config('hosting_provision.command'));

        $runningLog = 'Running command: '.$command;
        if ($folderNote !== null) {
            $runningLog = $folderNote."\n".$runningLog;
        }
        if ($vhostLog !== null) {
            $runningLog = $vhostLog."\n".$runningLog;
        }
        $hosting->update([
            'provision_status' => 'running',
            'provision_log' => $runningLog,
        ]);

        $process = Process::fromShellCommandline($command);
        $process->setTimeout((int) config('hosting_provision.timeout', 120));
        $process->run();

        $log = trim($process->getOutput()."\n".$process->getErrorOutput());

        if ($process->isSuccessful()) {
            $successLog = $log ?: 'Provision completed with no output.';
            if ($folderNote !== null) {
                $successLog = $folderNote."\n".$successLog;
            }
            if ($vhostLog !== null) {
                $successLog = $vhostLog."\n".$successLog;
            }
            $hosting->update([
                'provision_status' => 'success',
                'provision_log' => $successLog,
                'provisioned_at' => Carbon::now(),
            ]);

            return;
        }

        $failLog = $log ?: 'Provision failed with no output.';
        if ($folderNote !== null) {
            $failLog = $folderNote."\n".$failLog;
        }
        if ($vhostLog !== null) {
            $failLog = $vhostLog."\n".$failLog;
        }
        $hosting->update([
            'provision_status' => 'failed',
            'provision_log' => $failLog,
        ]);
    }

    /**
     * Run the configured remove shell command before the hosting row is deleted.
     *
     * @return bool True when disabled, or when the process exited successfully.
     */
    public function remove(Hosting $hosting): bool
    {
        $this->runNginxDeactivate($hosting);
        $this->runVhostRemove($hosting);

        if (! config('hosting_provision.remove_enabled')) {
            return true;
        }

        $command = $this->applyHostingPlaceholders($hosting, (string) config('hosting_provision.remove_command'));

        $process = Process::fromShellCommandline($command);
        $process->setTimeout((int) config('hosting_provision.timeout', 120));
        $process->run();

        $log = trim($process->getOutput()."\n".$process->getErrorOutput());

        Log::info('hosting_remove_cli', [
            'domain' => $hosting->domain,
            'id' => $hosting->id,
            'command' => $command,
            'success' => $process->isSuccessful(),
            'output' => $log,
        ]);

        return $process->isSuccessful();
    }

    /**
     * @return array{stopped: bool, message: string|null}
     */
    private function runVhostProvision(Hosting $hosting): array
    {
        if (! config('hosting_provision.vhost_enabled')) {
            return ['stopped' => false, 'message' => null];
        }

        $script = $this->resolveVhostScriptPath('vhost_script');
        if ($script === null) {
            return ['stopped' => false, 'message' => 'Vhost: skipped (HOSTING_VHOST_SCRIPT missing or not a file).'];
        }

        $outputDir = storage_path('app/hosting-vhosts');
        File::ensureDirectoryExists($outputDir);

        $process = new Process(
            ['bash', $script, $hosting->siteHost(), (string) $hosting->web_root_path],
            base_path(),
            [
                'HOSTING_VHOST_OUTPUT_DIR' => $outputDir,
                'PHP_FPM_SOCKET' => $this->resolvePhpFpmSocket($hosting),
            ],
            null,
            (float) config('hosting_provision.timeout', 120)
        );
        $process->run();

        $out = trim($process->getOutput()."\n".$process->getErrorOutput());

        Log::info('hosting_vhost_provision', [
            'domain' => $hosting->domain,
            'script' => $script,
            'success' => $process->isSuccessful(),
            'output' => $out,
        ]);

        if (! $process->isSuccessful()) {
            $msg = 'Vhost script failed: '.($out ?: 'no output');
            if (config('hosting_provision.vhost_stop_on_error')) {
                return ['stopped' => true, 'message' => $msg];
            }

            return ['stopped' => false, 'message' => $msg.' (continuing; HOSTING_VHOST_STOP_ON_ERROR=false)'];
        }

        $message = 'Vhost: '.($out ?: 'ok');

        $activate = $this->runNginxActivate($hosting, $outputDir);
        if ($activate['message'] !== null) {
            $message .= "\n".$activate['message'];
        }
        if ($activate['failed'] && config('hosting_provision.vhost_stop_on_error')) {
            return ['stopped' => true, 'message' => $message];
        }

        return ['stopped' => false, 'message' => $message];
    }

    /**
     * @return array{failed: bool, message: string|null}
     */
    private function runNginxActivate(Hosting $hosting, string $outputDir): array
    {
        if (! config('hosting_provision.vhost_nginx_activate')) {
            return ['failed' => false, 'message' => null];
        }

        $systemBin = $this->resolveNginxSystemBinary('xenweet-nginx-activate', 'vhost_nginx_system_activate');
        $script = $this->resolveVhostScriptPath('vhost_nginx_activate_script');

        if ($systemBin !== null) {
            // Second arg = output dir: sudo does not forward env to the root helper.
            $process = new Process(
                ['sudo', '-n', $systemBin, $hosting->siteHost(), $outputDir],
                base_path(),
                [],
                null,
                (float) config('hosting_provision.timeout', 120)
            );
            $process->run();

            $activateOut = trim($process->getOutput()."\n".$process->getErrorOutput());

            Log::info('hosting_vhost_nginx_activate', [
                'domain' => $hosting->domain,
                'script' => $systemBin,
                'system' => true,
                'success' => $process->isSuccessful(),
                'output' => $activateOut,
            ]);

            if (! $process->isSuccessful()) {
                return [
                    'failed' => true,
                    'message' => 'Nginx activate failed: '.($activateOut ?: 'no output').$this->nginxSudoHint($activateOut),
                ];
            }

            return [
                'failed' => false,
                'message' => 'Nginx activate: '.($activateOut ?: 'ok'),
            ];
        }

        if ($script === null) {
            return [
                'failed' => false,
                'message' => 'Nginx activate: skipped (install helpers: bash scripts/install-xenweet-nginx-sudo.sh on the server).',
            ];
        }

        $process = new Process(
            ['bash', $script, $hosting->siteHost(), $outputDir],
            base_path(),
            ['HOSTING_VHOST_OUTPUT_DIR' => $outputDir],
            null,
            (float) config('hosting_provision.timeout', 120)
        );
        $process->run();

        $activateOut = trim($process->getOutput()."\n".$process->getErrorOutput());

        Log::info('hosting_vhost_nginx_activate', [
            'domain' => $hosting->domain,
            'script' => $script,
            'system' => false,
            'success' => $process->isSuccessful(),
            'output' => $activateOut,
        ]);

        if (! $process->isSuccessful()) {
            return [
                'failed' => true,
                'message' => 'Nginx activate failed: '.($activateOut ?: 'no output').$this->nginxSudoHint($activateOut),
            ];
        }

        return [
            'failed' => false,
            'message' => 'Nginx activate: '.($activateOut ?: 'ok'),
        ];
    }

    private function runNginxDeactivate(Hosting $hosting): void
    {
        if (! config('hosting_provision.vhost_nginx_activate')) {
            return;
        }

        $systemBin = $this->resolveNginxSystemBinary('xenweet-nginx-deactivate', 'vhost_nginx_system_deactivate');
        $script = $this->resolveVhostScriptPath('vhost_nginx_deactivate_script');

        if ($systemBin !== null) {
            $process = new Process(
                ['sudo', '-n', $systemBin, $hosting->siteHost()],
                base_path(),
                [],
                null,
                (float) config('hosting_provision.timeout', 120)
            );
            $process->run();
            $out = trim($process->getOutput()."\n".$process->getErrorOutput());

            Log::info('hosting_vhost_nginx_deactivate', [
                'domain' => $hosting->domain,
                'script' => $systemBin,
                'system' => true,
                'success' => $process->isSuccessful(),
                'output' => $out,
            ]);

            return;
        }

        if ($script === null) {
            return;
        }

        $process = new Process(
            ['bash', $script, $hosting->siteHost()],
            base_path(),
            [],
            null,
            (float) config('hosting_provision.timeout', 120)
        );
        $process->run();

        $out = trim($process->getOutput()."\n".$process->getErrorOutput());

        Log::info('hosting_vhost_nginx_deactivate', [
            'domain' => $hosting->domain,
            'script' => $script,
            'system' => false,
            'success' => $process->isSuccessful(),
            'output' => $out,
        ]);
    }

    private function resolveNginxSystemBinary(string $basename, string $configKey): ?string
    {
        $configured = config('hosting_provision.'.$configKey);
        if (is_string($configured) && $configured !== '' && is_executable($configured)) {
            return $configured;
        }

        $default = '/usr/local/sbin/'.$basename;
        if (is_executable($default)) {
            return $default;
        }

        return null;
    }

    private function nginxSudoHint(string $output): string
    {
        if (! str_contains($output, 'password is required')) {
            return '';
        }

        return "\n\n[Hint] On the server run: bash scripts/install-xenweet-nginx-sudo.sh"
            ."\n(or set HOSTING_VHOST_NGINX_ACTIVATE=false and enable sites manually).";
    }

    private function runVhostRemove(Hosting $hosting): void
    {
        if (! config('hosting_provision.vhost_enabled')) {
            return;
        }

        $script = $this->resolveVhostScriptPath('vhost_remove_script');
        if ($script === null) {
            return;
        }

        $outputDir = storage_path('app/hosting-vhosts');

        $process = new Process(
            ['bash', $script, $hosting->siteHost()],
            base_path(),
            ['HOSTING_VHOST_OUTPUT_DIR' => $outputDir],
            null,
            (float) config('hosting_provision.timeout', 120)
        );
        $process->run();

        $out = trim($process->getOutput()."\n".$process->getErrorOutput());

        Log::info('hosting_vhost_remove', [
            'domain' => $hosting->domain,
            'script' => $script,
            'success' => $process->isSuccessful(),
            'output' => $out,
        ]);
    }

    private function resolveVhostScriptPath(string $key): ?string
    {
        $path = (string) config('hosting_provision.'.$key);
        if ($path === '' || ! is_file($path)) {
            return null;
        }

        return $path;
    }

    private function resolvePhpFpmSocket(Hosting $hosting): string
    {
        $override = config('hosting_provision.php_fpm_socket');
        if (is_string($override) && $override !== '') {
            return $override;
        }

        $v = trim((string) $hosting->php_version);
        if (preg_match('/^(\d+)\.(\d+)/', $v, $m)) {
            return '/var/run/php/php'.$m[1].'.'.$m[2].'-fpm.sock';
        }

        return '/var/run/php/php8.3-fpm.sock';
    }

    private function applyHostingPlaceholders(Hosting $hosting, string $template): string
    {
        return strtr($template, [
            '{id}' => (string) $hosting->id,
            '{domain}' => $hosting->domain,
            '{server_ip}' => $hosting->server_ip,
            '{plan}' => $hosting->plan,
            '{panel_username}' => $hosting->panel_username,
            '{panel_password}' => $hosting->panel_password,
            '{php_version}' => $hosting->php_version,
            '{status}' => $hosting->status,
            '{disk_usage_mb}' => (string) $hosting->disk_usage_mb,
            '{host_root_path}' => (string) $hosting->host_root_path,
            '{web_root_path}' => (string) $hosting->web_root_path,
        ]);
    }

    private function ensureHostFolders(Hosting $hosting): array
    {
        $configuredRoot = (string) config('hosting_provision.hosts_root', storage_path('app/hosting-sites'));
        $fallbackRoot = storage_path('app/hosting-sites');

        [$baseRoot, $folderNote] = $this->writableBaseRoot($configuredRoot, $fallbackRoot);
        $domainFolder = Str::lower(preg_replace('/[^a-zA-Z0-9\.\-_]/', '-', $hosting->domain) ?: 'site-'.$hosting->id);
        $hostRootPath = rtrim($baseRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$domainFolder;
        $webRootPath = $hostRootPath.DIRECTORY_SEPARATOR.'public_html';
        $logPath = $hostRootPath.DIRECTORY_SEPARATOR.'logs';
        $backupPath = $hostRootPath.DIRECTORY_SEPARATOR.'backups';
        $sslPath = $hostRootPath.DIRECTORY_SEPARATOR.'ssl';

        $this->mkdirSafe($hostRootPath);
        $this->mkdirSafe($webRootPath);
        $this->mkdirSafe($logPath);
        $this->mkdirSafe($backupPath);
        $this->mkdirSafe($sslPath);

        $indexFile = $webRootPath.DIRECTORY_SEPARATOR.'index.html';
        if (! File::exists($indexFile)) {
            File::put(
                $indexFile,
                "<!doctype html><html><head><meta charset=\"utf-8\"><title>{$hosting->domain}</title></head><body><h1>{$hosting->domain}</h1><p>Hosting root is ready.</p></body></html>"
            );
        }

        return [$hostRootPath, $webRootPath, $folderNote];
    }

    /**
     * Prefer HOSTING_SITES_ROOT; if PHP cannot create/write there, use storage (always writable in a normal Laravel deploy).
     *
     * @return array{0: string, 1: string|null}
     */
    private function writableBaseRoot(string $configuredRoot, string $fallbackRoot): array
    {
        if ($configuredRoot === $fallbackRoot) {
            return [$fallbackRoot, null];
        }

        try {
            $this->mkdirSafe($configuredRoot);

            if (is_writable($configuredRoot)) {
                return [$configuredRoot, null];
            }
        } catch (\Throwable) {
            // fall through to fallback
        }

        $note = 'HOSTING_SITES_ROOT was not usable ('.$configuredRoot.'); using '.$fallbackRoot.'. Fix permissions or chown so the PHP user can write there.';

        return [$fallbackRoot, $note];
    }

    private function mkdirSafe(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        File::ensureDirectoryExists($path);
    }
}
