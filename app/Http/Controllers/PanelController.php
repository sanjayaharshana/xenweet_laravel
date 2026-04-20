<?php

namespace App\Http\Controllers;

use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

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
        return view('panel.create-host');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:hostings,domain'],
            'server_ip' => ['required', 'ip'],
            'plan' => ['required', 'string', 'max:100'],
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
