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

        if (! config('hosting_provision.enabled')) {
            $log = 'Provision command disabled. Folder binding created at: '.$webRootPath;
            if ($folderNote !== null) {
                $log .= "\n".$folderNote;
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
