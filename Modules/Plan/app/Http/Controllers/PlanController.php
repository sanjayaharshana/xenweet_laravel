<?php

namespace Modules\Plan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Plan\Models\Plan;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()->latest()->paginate(12);

        return view('plan::index', compact('plans'));
    }

    public function create(): View
    {
        return view('plan::create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'disk_limit_mb' => ['required', 'integer', 'min:0'],
            'bandwidth_gb' => ['required', 'integer', 'min:0'],
            'max_domains' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ]);

        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Plan::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $data['slug'] = $slug;

        Plan::create($data);

        return redirect()->route('plan.index')->with('success', 'Hosting plan created successfully.');
    }

    public function show(Plan $plan): View
    {
        return view('plan::show', compact('plan'));
    }

    public function edit(Plan $plan): View
    {
        return view('plan::edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'disk_limit_mb' => ['required', 'integer', 'min:0'],
            'bandwidth_gb' => ['required', 'integer', 'min:0'],
            'max_domains' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ]);

        if ($plan->name !== $data['name']) {
            $baseSlug = Str::slug($data['name']);
            $slug = $baseSlug;
            $counter = 1;

            while (Plan::query()->where('slug', $slug)->whereKeyNot($plan->id)->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            $data['slug'] = $slug;
        }

        $plan->update($data);

        return redirect()->route('plan.index')->with('success', 'Hosting plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('plan.index')->with('success', 'Hosting plan deleted.');
    }
}
