<?php

namespace App\Services;

use App\Models\Hosting;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class HostingCliProvisioner
{
    public function run(Hosting $hosting): void
    {
        [$hostRootPath, $webRootPath] = $this->ensureHostFolders($hosting);

        $hosting->update([
            'host_root_path' => $hostRootPath,
            'web_root_path' => $webRootPath,
        ]);

        if (! config('hosting_provision.enabled')) {
            $hosting->update([
                'provision_status' => 'success',
                'provision_log' => 'Provision command disabled. Folder binding created at: '.$webRootPath,
                'provisioned_at' => Carbon::now(),
            ]);

            return;
        }

        $command = $this->resolveCommandTemplate($hosting);

        $hosting->update([
            'provision_status' => 'running',
            'provision_log' => 'Running command: '.$command,
        ]);

        $process = Process::fromShellCommandline($command);
        $process->setTimeout((int) config('hosting_provision.timeout', 120));
        $process->run();

        $log = trim($process->getOutput()."\n".$process->getErrorOutput());

        if ($process->isSuccessful()) {
            $hosting->update([
                'provision_status' => 'success',
                'provision_log' => $log ?: 'Provision completed with no output.',
                'provisioned_at' => Carbon::now(),
            ]);

            return;
        }

        $hosting->update([
            'provision_status' => 'failed',
            'provision_log' => $log ?: 'Provision failed with no output.',
        ]);
    }

    private function resolveCommandTemplate(Hosting $hosting): string
    {
        $template = (string) config('hosting_provision.command');

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
        $baseRoot = (string) config('hosting_provision.hosts_root', storage_path('app/hosting-sites'));
        $domainFolder = Str::lower(preg_replace('/[^a-zA-Z0-9\.\-_]/', '-', $hosting->domain) ?: 'site-'.$hosting->id);
        $hostRootPath = rtrim($baseRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$domainFolder;
        $webRootPath = $hostRootPath.DIRECTORY_SEPARATOR.'public_html';
        $logPath = $hostRootPath.DIRECTORY_SEPARATOR.'logs';
        $backupPath = $hostRootPath.DIRECTORY_SEPARATOR.'backups';
        $sslPath = $hostRootPath.DIRECTORY_SEPARATOR.'ssl';

        File::ensureDirectoryExists($hostRootPath);
        File::ensureDirectoryExists($webRootPath);
        File::ensureDirectoryExists($logPath);
        File::ensureDirectoryExists($backupPath);
        File::ensureDirectoryExists($sslPath);

        $indexFile = $webRootPath.DIRECTORY_SEPARATOR.'index.html';
        if (! File::exists($indexFile)) {
            File::put(
                $indexFile,
                "<!doctype html><html><head><meta charset=\"utf-8\"><title>{$hosting->domain}</title></head><body><h1>{$hosting->domain}</h1><p>Hosting root is ready.</p></body></html>"
            );
        }

        return [$hostRootPath, $webRootPath];
    }
}
