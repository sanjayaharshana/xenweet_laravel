@extends('layouts.panel')

@section('title', 'Hosting Plans - Xenweet Panel')

@section('content')
    <header class="topbar">
        <div>
            <p class="eyebrow">Plan Module</p>
            <h1>Hosting Plans</h1>
            <p class="subtle">Manage all hosting plan packages in one place.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-primary compact" href="{{ route('plan.create') }}">+ Create Plan</a>
        </div>
    </header>

    @if (session('success'))
        <section class="flash-success">{{ session('success') }}</section>
    @endif

    <main class="hosting-list-shell">
        @forelse ($plans as $plan)
            <article class="hosting-row">
                <div>
                    <p class="label">Plan</p>
                    <strong>{{ $plan->name }}</strong>
                </div>
                <div>
                    <p class="label">Price</p>
                    <strong>${{ number_format($plan->monthly_price, 2) }}/mo</strong>
                </div>
                <div>
                    <p class="label">Disk</p>
                    <strong>{{ $plan->disk_limit_mb }} MB</strong>
                </div>
                <div>
                    <p class="label">Bandwidth</p>
                    <strong>{{ $plan->bandwidth_gb }} GB</strong>
                </div>
                <div>
                    <p class="label">Max Domains</p>
                    <strong>{{ $plan->max_domains }}</strong>
                </div>
                <div>
                    <p class="label">Status</p>
                    <span class="status online">{{ ucfirst($plan->status) }}</span>
                </div>
                <div>
                    <p class="label">Slug</p>
                    <strong>{{ $plan->slug }}</strong>
                </div>
                <div class="plan-actions">
                    <a class="btn-secondary compact-btn" href="{{ route('plan.show', $plan) }}">View</a>
                    <a class="btn-secondary compact-btn" href="{{ route('plan.edit', $plan) }}">Edit</a>
                    <form action="{{ route('plan.destroy', $plan) }}" method="POST" onsubmit="return confirm('Delete this plan?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-secondary compact-btn danger-btn">Delete</button>
                    </form>
                </div>
            </article>
        @empty
            <article class="server-card empty-state">
                <h2>No plans found</h2>
                <p>Create your first hosting plan to start assigning packages.</p>
                <a class="btn-primary compact" href="{{ route('plan.create') }}">Create Plan</a>
            </article>
        @endforelse
    </main>

    <div style="margin-top: 1rem;">
        {{ $plans->links() }}
    </div>
@endsection
