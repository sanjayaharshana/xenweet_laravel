<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

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

        $stateRoot = $this->resolveWritableStateRoot();
        if ($stateRoot === null) {
            return ['ok' => false, 'message' => 'Mail stack state root is not writable. Check MAIL_STACK_STATE_ROOT permissions.'];
        }

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
            $msg = $output !== '' ? $output : 'Mailbox provision failed with no output.';

            return [
                'ok' => false,
                'message' => $msg.' (state root: '.$stateRoot.')',
            ];
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

        $stateRoot = $this->resolveWritableStateRoot();
        if ($stateRoot === null) {
            return ['ok' => false, 'message' => 'Mail stack state root is not writable. Check MAIL_STACK_STATE_ROOT permissions.'];
        }

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
            $msg = $output !== '' ? $output : 'Mailbox remove failed with no output.';

            return [
                'ok' => false,
                'message' => $msg.' (state root: '.$stateRoot.')',
            ];
        }

        return ['ok' => true, 'message' => $output !== '' ? $output : 'Mailbox removed.'];
    }

    private function resolveWritableStateRoot(): ?string
    {
        $configured = trim((string) config('mail_stack.state_root'));
        $fallback = storage_path('app/mailstack');
        $candidateRoots = array_values(array_unique(array_filter([$configured, $fallback])));

        foreach ($candidateRoots as $root) {
            try {
                File::ensureDirectoryExists($root);
                if (is_dir($root) && is_writable($root)) {
                    if ($root !== $configured && $configured !== '') {
                        Log::warning('mail_stack_state_root_fallback', [
                            'configured' => $configured,
                            'fallback' => $root,
                        ]);
                    }

                    return $root;
                }
            } catch (Throwable $e) {
                Log::warning('mail_stack_state_root_unwritable', [
                    'path' => $root,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }
}
