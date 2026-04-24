<?php

namespace Modules\SshAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\JsonResponse;
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
            'activeTab' => 'ssh',
        ]);
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
