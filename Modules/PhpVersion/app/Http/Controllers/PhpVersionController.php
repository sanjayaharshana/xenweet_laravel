<?php

namespace Modules\PhpVersion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use App\Services\HostingCliProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PhpVersionController extends Controller
{
    public function __construct(
        private readonly HostingCliProvisioner $provisioner
    ) {}

    public function index(Hosting $hosting): View
    {
        $activeTab = request()->query('tab', 'version');
        if (! in_array($activeTab, ['version', 'extensions', 'options'], true)) {
            $activeTab = 'version';
        }

        $allowed = config('phpversion.available_versions', ['8.3']);
        if (! in_array($hosting->php_version, $allowed, true)) {
            $allowed = array_values(array_unique(array_merge($allowed, [(string) $hosting->php_version])));
        }

        $availableExtensions = array_values(array_unique(array_filter((array) config('phpversion.available_extensions', []))));
        $enabledExtensions = array_values(array_unique(array_filter((array) ($hosting->php_extensions ?? []))));
        $configuredIniOptions = (array) config('phpversion.ini_options', []);
        $storedIniOptions = (array) ($hosting->php_ini_options ?? []);
        $phpIniOptions = collect($configuredIniOptions)
            ->map(function (array $meta, string $key) use ($storedIniOptions): array {
                $default = (string) ($meta['default'] ?? '');
                $value = array_key_exists($key, $storedIniOptions)
                    ? (string) $storedIniOptions[$key]
                    : $default;

                return [
                    'key' => $key,
                    'label' => (string) ($meta['label'] ?? $key),
                    'type' => (string) ($meta['type'] ?? 'text'),
                    'help' => (string) ($meta['help'] ?? ''),
                    'default' => $default,
                    'value' => $value,
                ];
            })
            ->values()
            ->all();

        return view('phpversion::index', [
            'hosting' => $hosting,
            'activeTab' => $activeTab,
            'allowedVersions' => $allowed,
            'availableExtensions' => $availableExtensions,
            'enabledExtensions' => $enabledExtensions,
            'phpIniOptions' => $phpIniOptions,
            'fpmSocket' => $hosting->webPhpFpmSocketPath(),
            'vhostEnabled' => (bool) config('hosting_provision.vhost_enabled', false),
        ]);
    }

    public function update(Request $request, Hosting $hosting): RedirectResponse
    {
        $allowed = config('phpversion.available_versions', ['8.3']);
        if ($allowed === []) {
            $allowed = ['8.3'];
        }

        $request->validate([
            'php_version' => ['required', 'string', 'max:20', Rule::in($allowed)],
        ]);

        $hosting->update([
            'php_version' => $request->string('php_version'),
        ]);
        $hosting->refresh();

        $vhost = $this->provisioner->reapplyWebVhost($hosting);

        if (! $vhost['success']) {
            return redirect()
                ->route('hosts.php-version', $hosting)
                ->with('error', 'PHP version saved, but the web server config could not be updated: '.$vhost['message']);
        }

        return redirect()
            ->route('hosts.php-version', $hosting)
            ->with('success', 'PHP version updated for the website. '.$vhost['message']);
    }

    public function updateExtensions(Request $request, Hosting $hosting): RedirectResponse
    {
        $availableExtensions = array_values(array_unique(array_filter((array) config('phpversion.available_extensions', []))));

        $validated = $request->validate([
            'extensions' => ['nullable', 'array'],
            'extensions.*' => ['string', Rule::in($availableExtensions)],
        ]);

        if (! Schema::hasColumn('hostings', 'php_extensions')) {
            return redirect()
                ->route('hosts.php-version', ['hosting' => $hosting, 'tab' => 'extensions'])
                ->with('error', 'PHP extensions storage is not ready yet. Run migrations first.');
        }

        $enabled = array_values(array_unique(array_filter((array) ($validated['extensions'] ?? []))));

        $hosting->update([
            'php_extensions' => $enabled,
        ]);

        return redirect()
            ->route('hosts.php-version', ['hosting' => $hosting, 'tab' => 'extensions'])
            ->with('success', 'PHP extension preferences updated.');
    }

    public function updateIniOptions(Request $request, Hosting $hosting): RedirectResponse
    {
        $iniOptions = (array) config('phpversion.ini_options', []);
        $allowedKeys = array_keys($iniOptions);

        $validated = $request->validate([
            'ini' => ['nullable', 'array'],
            'ini.*' => ['nullable', 'string', 'max:255'],
        ]);

        $submitted = (array) ($validated['ini'] ?? []);
        $filtered = [];

        foreach ($allowedKeys as $key) {
            $type = (string) ($iniOptions[$key]['type'] ?? 'text');
            $value = trim((string) ($submitted[$key] ?? ''));
            if ($type === 'boolean') {
                $value = in_array(strtolower($value), ['1', 'on', 'true', 'yes'], true) ? 'On' : 'Off';
            }
            if ($value === '') {
                $value = (string) ($iniOptions[$key]['default'] ?? '');
            }
            $filtered[$key] = $value;
        }

        if (! Schema::hasColumn('hostings', 'php_ini_options')) {
            return redirect()
                ->route('hosts.php-version', ['hosting' => $hosting, 'tab' => 'options'])
                ->with('error', 'PHP options storage is not ready yet. Run migrations first.');
        }

        $hosting->update([
            'php_ini_options' => $filtered,
        ]);

        return redirect()
            ->route('hosts.php-version', ['hosting' => $hosting, 'tab' => 'options'])
            ->with('success', 'PHP options updated.');
    }
}
