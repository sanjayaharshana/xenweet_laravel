<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class MailStackProvisioner
{
    public function provisionMailbox(string $email, string $passwordHash): array
    {
        if (! config('mail_stack.enabled')) {
            return ['ok' => true, 'message' => 'Mail stack provisioning disabled.'];
        }

        $script = (string) config('mail_stack.provision_script');
        if ($script === '' || ! is_file($script)) {
            return ['ok' => false, 'message' => 'Provision script missing: '.$script];
        }

        [$localPart, $domain] = array_pad(explode('@', $email, 2), 2, null);
        if (! $localPart || ! $domain) {
            return ['ok' => false, 'message' => 'Invalid mailbox email format.'];
        }

        $stateRoot = (string) config('mail_stack.state_root');
        File::ensureDirectoryExists($stateRoot);

        $process = new Process(
            ['bash', $script, $domain, $localPart, $passwordHash, $stateRoot],
            base_path(),
            [],
            null,
            (float) config('mail_stack.timeout', 45)
        );
        $process->run();

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());
        if (! $process->isSuccessful()) {
            return ['ok' => false, 'message' => $output !== '' ? $output : 'Mailbox provision failed with no output.'];
        }

        return ['ok' => true, 'message' => $output !== '' ? $output : 'Mailbox provisioned.'];
    }

    public function removeMailbox(string $email): array
    {
        if (! config('mail_stack.enabled')) {
            return ['ok' => true, 'message' => 'Mail stack provisioning disabled.'];
        }

        $script = (string) config('mail_stack.remove_script');
        if ($script === '' || ! is_file($script)) {
            return ['ok' => false, 'message' => 'Remove script missing: '.$script];
        }

        [$localPart, $domain] = array_pad(explode('@', $email, 2), 2, null);
        if (! $localPart || ! $domain) {
            return ['ok' => false, 'message' => 'Invalid mailbox email format.'];
        }

        $stateRoot = (string) config('mail_stack.state_root');
        File::ensureDirectoryExists($stateRoot);

        $process = new Process(
            ['bash', $script, $domain, $localPart, $stateRoot],
            base_path(),
            [],
            null,
            (float) config('mail_stack.timeout', 45)
        );
        $process->run();

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());
        if (! $process->isSuccessful()) {
            return ['ok' => false, 'message' => $output !== '' ? $output : 'Mailbox remove failed with no output.'];
        }

        return ['ok' => true, 'message' => $output !== '' ? $output : 'Mailbox removed.'];
    }
}
