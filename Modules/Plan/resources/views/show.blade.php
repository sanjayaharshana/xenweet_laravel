@extends('layouts.panel')

@section('title', 'Plan Details - Xenweet Panel')

@section('content')
    <header class="topbar">
        <div>
            <p class="eyebrow">Plan Module</p>
            <h1>{{ $plan->name }}</h1>
            <p class="subtle">Plan details and package limits.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('plan.index') }}">Back to Plans</a>
            <a class="btn-primary compact" href="{{ route('plan.edit', $plan) }}">Edit Plan</a>
        </div>
    </header>

    <article class="server-card">
        <div class="meta">
            <div><span>Slug</span><strong>{{ $plan->slug }}</strong></div>
            <div><span>Monthly Price</span><strong>${{ number_format($plan->monthly_price, 2) }}</strong></div>
            <div><span>Disk Limit</span><strong>{{ $plan->disk_limit_mb }} MB</strong></div>
            <div><span>Bandwidth</span><strong>{{ $plan->bandwidth_gb }} GB</strong></div>
            <div><span>Max Domains</span><strong>{{ $plan->max_domains }}</strong></div>
            <div><span>Status</span><strong>{{ ucfirst($plan->status) }}</strong></div>
        </div>

        <div class="service-list">
            <div>
                <span>Description</span>
                <strong>{{ $plan->description ?: 'No description' }}</strong>
            </div>
        </div>
    </article>
@endsection
