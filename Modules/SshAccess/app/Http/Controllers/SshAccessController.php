<?php

namespace Modules\SshAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class SshAccessController extends Controller
{
    public function index(Hosting $hosting): View
    {
        $sshHost = trim((string) ($hosting->server_ip ?: $hosting->siteHost()));
        $sshUser = trim((string) ($hosting->panel_username ?: 'root'));
        $port = 22;

        return view('sshaccess::index', [
            'hosting' => $hosting,
            'sshHost' => $sshHost,
            'sshUser' => $sshUser,
            'sshPort' => $port,
            'sshCommand' => sprintf('ssh -p %d %s@%s', $port, $sshUser, $sshHost),
            'scpCommand' => sprintf('scp -P %d ./local-file.txt %s@%s:~/', $port, $sshUser, $sshHost),
            'rsyncCommand' => sprintf('rsync -avz -e "ssh -p %d" ./ %s@%s:~/', $port, $sshUser, $sshHost),
            'sshCreateUrl' => route('hosts.ssh-access.create-account', $hosting),
            'activeTab' => 'ssh',
        ]);
    }

    public function createJailedAccount(Request $request, Hosting $hosting): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-z_][a-z0-9_-]{2,31}$/'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'public_key' => ['nullable', 'string', 'max:65535'],
        ]);

        $hostRoot = trim((string) ($hosting->host_root_path ?? ''));
        $webRoot = trim((string) ($hosting->web_root_path ?? ''));
        if ($hostRoot === '' || ! is_dir($hostRoot)) {
            return back()->with('sshaccess_error', 'Host root path is missing or invalid for this hosting account.');
        }
        if ($webRoot === '' || ! is_dir($webRoot)) {
            return back()->with('sshaccess_error', 'Web root path is missing or invalid for this hosting account.');
        }

        $username = (string) $validated['username'];
        $password = (string) $validated['password'];
        $publicKey = trim((string) ($validated['public_key'] ?? ''));
        $publicKeyB64 = $publicKey !== '' ? base64_encode($publicKey) : '';

        $systemBin = (string) config('sshaccess.create_account_system_bin', '/usr/local/sbin/xenweet-ssh-create-jailed');
        $script = (string) config('sshaccess.create_account_script', base_path('scripts/hosting-ssh-create-jailed.sh'));
        $timeout = (float) config('sshaccess.create_account_timeout', 60);

        if ($systemBin !== '' && is_executable($systemBin)) {
            $command = ['sudo', '-n', $systemBin, $username, $password, $hostRoot, $webRoot, $publicKeyB64];
        } elseif ($script !== '' && is_file($script)) {
            $command = ['bash', $script, $username, $password, $hostRoot, $webRoot, $publicKeyB64];
        } else {
            return back()->with(
                'sshaccess_error',
                'SSH account creator is not configured. Set SSHACCESS_CREATE_ACCOUNT_SYSTEM_BIN or SSHACCESS_CREATE_ACCOUNT_SCRIPT.'
            );
        }

        $process = new Process($command, base_path(), null, null, $timeout);
        $process->run();
        $out = trim($process->getOutput()."\n".$process->getErrorOutput());

        if (! $process->isSuccessful()) {
            if (str_contains(strtolower($out), 'a password is required')) {
                $hint = 'sudo requires password. On server run: bash scripts/install-xenweet-ssh-sudo.sh www-data';

                return back()->with('sshaccess_error', 'Create SSH account failed: '.$hint);
            }

            return back()->with('sshaccess_error', 'Create SSH account failed: '.($out !== '' ? $out : 'no output'));
        }

        $msg = 'SSH jailed account created: '.$username;
        if ($out !== '' && mb_strlen($out) < 500) {
            $msg .= ' — '.$out;
        }

        return back()->with('sshaccess_success', $msg);
    }

    public function terminal(Hosting $hosting): View
    {
        return view('sshaccess::terminal', [
            'hosting' => $hosting,
            'activeTab' => 'terminal',
            'terminalRunUrl' => route('hosts.terminal.run', $hosting),
            'terminalCwd' => $this->resolveWorkingDirectory($hosting),
        ]);
    }

    public function runTerminalCommand(Request $request, Hosting $hosting): JsonResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string', 'max:4000'],
        ]);

        $command = trim((string) $validated['command']);
        if ($command === '') {
            return response()->json([
                'ok' => false,
                'output' => '',
                'exit_code' => 1,
                'error' => 'Command is empty.',
            ], 422);
        }

        $process = new Process(
            ['bash', '-lc', $command],
            $this->resolveWorkingDirectory($hosting),
            null,
            null,
            15
        );
        $process->run();

        $output = (string) $process->getOutput();
        $error = (string) $process->getErrorOutput();
        $combined = trim($output.$error);
        if (mb_strlen($combined) > 20000) {
            $combined = mb_substr($combined, 0, 20000)."\n[output truncated]";
        }

        return response()->json([
            'ok' => $process->isSuccessful(),
            'output' => $combined,
            'exit_code' => $process->getExitCode(),
        ]);
    }

    private function resolveWorkingDirectory(Hosting $hosting): string
    {
        $webRoot = trim((string) ($hosting->web_root_path ?? ''));
        if ($webRoot !== '' && is_dir($webRoot)) {
            return $webRoot;
        }

        $hostRoot = trim((string) ($hosting->host_root_path ?? ''));
        if ($hostRoot !== '' && is_dir($hostRoot)) {
            return $hostRoot;
        }

        return base_path();
    }
}
