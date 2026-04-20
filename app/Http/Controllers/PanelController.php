<?php

namespace App\Http\Controllers;

use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        Hosting::create($validated);

        return redirect()
            ->route('panel')
            ->with('success', 'Hosting created successfully.');
    }
}
