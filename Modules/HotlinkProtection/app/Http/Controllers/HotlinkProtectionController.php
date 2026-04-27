<?php

namespace Modules\HotlinkProtection\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HotlinkProtection\Services\HotlinkProtectionService;

class HotlinkProtectionController extends Controller
{
    public function index(Hosting $hosting, HotlinkProtectionService $service): View
    {
        $config = $service->getConfig($hosting);

        return view('hotlinkprotection::index', [
            'hosting' => $hosting,
            'config' => $config,
        ]);
    }

    public function update(Request $request, Hosting $hosting, HotlinkProtectionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'allow_direct_requests' => ['nullable', 'boolean'],
            'allowed_domains_raw' => ['required', 'string', 'max:8000'],
            'blocked_extensions_raw' => ['required', 'string', 'max:8000'],
        ]);

        $result = $service->saveConfig($hosting, [
            'enabled' => (bool) ($validated['enabled'] ?? false),
            'allow_direct_requests' => (bool) ($validated['allow_direct_requests'] ?? false),
            'allowed_domains_raw' => (string) $validated['allowed_domains_raw'],
            'blocked_extensions_raw' => (string) $validated['blocked_extensions_raw'],
        ]);

        if (! $result['ok']) {
            return redirect()
                ->route('hosts.hotlink-protection', $hosting)
                ->withErrors($result['errors'] ?? ['action' => 'Unable to save hotlink protection settings.'])
                ->withInput();
        }

        return redirect()
            ->route('hosts.hotlink-protection', $hosting)
            ->with('success', (string) ($result['message'] ?? 'Saved.'));
    }
}
