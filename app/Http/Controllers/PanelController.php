<?php

namespace App\Http\Controllers;

use App\Models\Hosting;
use App\Services\HostingCliProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Plan\Models\Plan;

class PanelController extends Controller
{
    public function index(): View
    {
        $hostings = Hosting::query()
            ->latest()
            ->get();

        return view('panel.dashboard', [
            'hostings' => $hostings,
            'lastSync' => Carbon::now()->format('d M Y, H:i'),
        ]);
    }

    public function create(): View
    {
        $plans = Plan::query()
            ->where('status', 'active')
            ->orderBy('monthly_price')
            ->get(['name']);

        $currentServerIp = request()->server('SERVER_ADDR')
            ?? gethostbyname(gethostname())
            ?? request()->ip();

        return view('panel.create-host', compact('plans', 'currentServerIp'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:hostings,domain'],
            'server_ip' => ['required', 'ip'],
            'plan' => [
                'required',
                'string',
                'max:100',
                Rule::exists('plans', 'name')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'panel_username' => ['required', 'string', 'max:100'],
            'panel_password' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'php_version' => ['required', 'string', 'max:20'],
            'disk_usage_mb' => ['required', 'integer', 'min:0'],
        ]);

        $hosting = Hosting::create($validated);
        app(HostingCliProvisioner::class)->run($hosting);

        return redirect()
            ->route('panel')
            ->with('success', 'Hosting created and CLI provisioning executed.');
    }

    public function hostPanel(Hosting $hosting, Request $request): View
    {
        $hostRoot = $this->hostRootPath($hosting);
        File::ensureDirectoryExists($hostRoot);

        $requestedPath = (string) $request->query('path', '');
        $currentPath = $this->safeResolvedPath($hostRoot, $requestedPath);
        $relativePath = ltrim(Str::after($currentPath, $hostRoot), DIRECTORY_SEPARATOR);

        $items = collect(File::files($currentPath))
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'type' => 'file',
                'size' => $this->humanSize($file->getSize()),
                'path' => ltrim(Str::after($file->getPathname(), $hostRoot), DIRECTORY_SEPARATOR),
                'modified' => Carbon::createFromTimestamp($file->getMTime())->format('d M Y H:i'),
            ])
            ->merge(
                collect(File::directories($currentPath))->map(fn ($directory) => [
                    'name' => basename($directory),
                    'type' => 'directory',
                    'size' => '--',
                    'path' => ltrim(Str::after($directory, $hostRoot), DIRECTORY_SEPARATOR),
                    'modified' => Carbon::createFromTimestamp(filemtime($directory))->format('d M Y H:i'),
                ])
            )
            ->sortBy([['type', 'asc'], ['name', 'asc']])
            ->values();

        $parentPath = '';
        if ($relativePath !== '') {
            $parentPath = dirname($relativePath);
            $parentPath = $parentPath === '.' ? '' : $parentPath;
        }

        return view('panel.host-panel', [
            'hosting' => $hosting,
            'items' => $items,
            'relativePath' => $relativePath,
            'parentPath' => $parentPath,
        ]);
    }

    private function hostRootPath(Hosting $hosting): string
    {
        if (! empty($hosting->host_root_path)) {
            return $hosting->host_root_path;
        }

        return storage_path('app/hosting-sites/'.$hosting->domain);
    }

    private function safeResolvedPath(string $hostRoot, string $relativePath): string
    {
        $clean = trim(str_replace('\\', '/', $relativePath), '/');
        $candidate = $clean === '' ? $hostRoot : $hostRoot.DIRECTORY_SEPARATOR.$clean;
        $real = realpath($candidate);

        if ($real === false || ! Str::startsWith($real, $hostRoot)) {
            return $hostRoot;
        }

        return $real;
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2).' MB';
        }

        return round($bytes / (1024 * 1024 * 1024), 2).' GB';
    }
}
