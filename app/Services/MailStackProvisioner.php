<?php

namespace App\Services;

use App\Models\Hosting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

class MailStackProvisioner
{
    public function provisionMailbox(Hosting $hosting, string $email, string $passwordHash): array
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

        $mailRoot = $this->resolveWritableMailRoot($hosting);
        if ($mailRoot === null) {
            return ['ok' => false, 'message' => 'Mail storage root is not writable. Check host path permissions.'];
        }

        $process = new Process(
            ['bash', $script, $domain, $localPart, $passwordHash, $mailRoot],
            base_path(),
            [],
            null,
            (float) config('mail_stack.timeout', 45)
        );
        $process->run();

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());
        if (! $process->isSuccessful()) {
            $msg = $output !== '' ? $output : 'Mailbox provision failed with no output.';

            return [
                'ok' => false,
                'message' => $msg.' (mail root: '.$mailRoot.')',
            ];
        }

        return ['ok' => true, 'message' => $output !== '' ? $output : 'Mailbox provisioned.'];
    }

    public function removeMailbox(Hosting $hosting, string $email): array
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

        $mailRoot = $this->resolveWritableMailRoot($hosting);
        if ($mailRoot === null) {
            return ['ok' => false, 'message' => 'Mail storage root is not writable. Check host path permissions.'];
        }

        $process = new Process(
            ['bash', $script, $domain, $localPart, $mailRoot],
            base_path(),
            [],
            null,
            (float) config('mail_stack.timeout', 45)
        );
        $process->run();

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());
        if (! $process->isSuccessful()) {
            $msg = $output !== '' ? $output : 'Mailbox remove failed with no output.';

            return [
                'ok' => false,
                'message' => $msg.' (mail root: '.$mailRoot.')',
            ];
        }

        return ['ok' => true, 'message' => $output !== '' ? $output : 'Mailbox removed.'];
    }

    private function resolveWritableMailRoot(Hosting $hosting): ?string
    {
        $hostRoot = trim((string) $hosting->host_root_path);
        $hostMailRoot = $hostRoot !== ''
            ? rtrim($hostRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.(string) config('mail_stack.host_mail_dir', 'mail')
            : '';

        $configured = trim((string) config('mail_stack.state_root'));
        $fallback = storage_path('app/mailstack');
        $candidateRoots = array_values(array_unique(array_filter([$hostMailRoot, $configured, $fallback])));

        foreach ($candidateRoots as $root) {
            try {
                File::ensureDirectoryExists($root);
                if (is_dir($root) && is_writable($root)) {
                    if ($hostMailRoot !== '' && $root !== $hostMailRoot) {
                        Log::warning('mail_stack_host_root_fallback', [
                            'hosting_id' => $hosting->id,
                            'host_mail_root' => $hostMailRoot,
                            'fallback' => $root,
                        ]);
                    }

                    return $root;
                }
            } catch (Throwable $e) {
                Log::warning('mail_stack_root_unwritable', [
                    'hosting_id' => $hosting->id,
                    'path' => $root,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }
}
