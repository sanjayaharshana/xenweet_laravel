<?php

namespace App\Http\Controllers;

use App\Models\Hosting;
use App\Services\HostingCliProvisioner;
use App\Services\PublicIpResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
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

        $publicIp = app(PublicIpResolver::class)->resolve();
        $localHint = request()->server('SERVER_ADDR')
            ?? gethostbyname(gethostname())
            ?? request()->ip();
        $currentServerIp = filled($publicIp) ? $publicIp : $localHint;

        return view('panel.create-host', compact('plans', 'currentServerIp'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'domain' => Hosting::normalizeDomainName((string) $request->input('domain', '')),
        ]);

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

    public function destroy(Hosting $hosting): RedirectResponse
    {
        $domain = $hosting->domain;
        $removeCliOk = app(HostingCliProvisioner::class)->remove($hosting);
        $hosting->delete();

        $message = 'Hosting "'.$domain.'" removed.';
        if (config('hosting_provision.remove_enabled')) {
            $message .= $removeCliOk
                ? ' Remove script completed.'
                : ' Remove script failed — details are in the application log.';
        }

        return redirect()
            ->route('panel')
            ->with('success', $message);
    }

    public function hostPanel(Hosting $hosting): View
    {
        return view('panel.host-panel', [
            'hosting' => $hosting,
            'hostPanelCategories' => $this->resolveHostPanelCategories($hosting),
        ]);
    }

    /**
     * @return list<array{id?: string, title: string, items: list<array<string, mixed>>}>
     */
    private function resolveHostPanelCategories(Hosting $hosting): array
    {
        return collect(config('host_panel.categories', []))
            ->map(function (array $category) use ($hosting) {
                $items = collect($category['items'] ?? [])
                    ->map(function (array $item) use ($hosting) {
                        $href = null;
                        if (! empty($item['route']) && Route::has($item['route'])) {
                            $params = array_merge($item['route_parameters'] ?? [], ['hosting' => $hosting]);
                            $href = route($item['route'], $params);
                        } elseif (! empty($item['url'])) {
                            $href = $item['url'];
                        }

                        return array_merge($item, [
                            'href' => $href,
                        ]);
                    })
                    ->values()
                    ->all();

                return array_merge($category, ['items' => $items]);
            })
            ->values()
            ->all();
    }
}
