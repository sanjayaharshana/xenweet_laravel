<?php

namespace Modules\PhpVersion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use App\Services\HostingCliProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PhpVersionController extends Controller
{
    public function __construct(
        private readonly HostingCliProvisioner $provisioner
    ) {}

    public function index(Hosting $hosting): View
    {
        $allowed = config('phpversion.available_versions', ['8.3']);
        if (! in_array($hosting->php_version, $allowed, true)) {
            $allowed = array_values(array_unique(array_merge($allowed, [(string) $hosting->php_version])));
        }

        return view('phpversion::index', [
            'hosting' => $hosting,
            'allowedVersions' => $allowed,
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
}
