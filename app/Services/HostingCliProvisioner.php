<?php

namespace App\Services;

use App\Models\Hosting;
use Carbon\Carbon;
use Symfony\Component\Process\Process;

class HostingCliProvisioner
{
    public function run(Hosting $hosting): void
    {
        if (! config('hosting_provision.enabled')) {
            $hosting->update([
                'provision_status' => 'success',
                'provision_log' => 'Provision command disabled in config.',
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
        ]);
    }
}
