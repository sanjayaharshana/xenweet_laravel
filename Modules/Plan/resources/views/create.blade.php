@extends('layouts.panel')

@section('title', 'Create Plan - Xenweet Panel')

@section('content')
    <section class="form-card">
        <h1>Create Hosting Plan</h1>
        <p class="subtle">Define a new hosting package with pricing and limits.</p>

        @if ($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('plan.store') }}" class="host-form">
            @csrf

            <label for="name">Plan Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required>

            <label for="monthly_price">Monthly Price (USD)</label>
            <input id="monthly_price" name="monthly_price" type="number" min="0" step="0.01" value="{{ old('monthly_price', 0) }}" required>

            <label for="disk_limit_mb">Disk Limit (MB)</label>
            <input id="disk_limit_mb" name="disk_limit_mb" type="number" min="0" value="{{ old('disk_limit_mb', 0) }}" required>

            <label for="bandwidth_gb">Bandwidth (GB)</label>
            <input id="bandwidth_gb" name="bandwidth_gb" type="number" min="0" value="{{ old('bandwidth_gb', 0) }}" required>

            <label for="max_domains">Max Domains</label>
            <input id="max_domains" name="max_domains" type="number" min="1" value="{{ old('max_domains', 1) }}" required>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
            </select>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4">{{ old('description') }}</textarea>

            <div class="form-actions">
                <a href="{{ route('plan.index') }}" class="btn-secondary link-button">Cancel</a>
                <button type="submit" class="btn-primary">Create Plan</button>
            </div>
        </form>
    </section>
@endsection

@section('right_sidebar')
    <div class="tips-panel">
        <h2>Plan Tips & Tricks</h2>
        <p class="subtle">Make plans clearer for customers and easier to manage.</p>

        <div class="tip-item">
            <h3>Split by customer profile</h3>
            <p>Starter for personal sites, Pro for business websites, and Enterprise for high-traffic projects.</p>
        </div>
        <div class="tip-item">
            <h3>Resource ladder</h3>
            <p>Increase disk, bandwidth, and max domains gradually so upgrades feel natural and measurable.</p>
        </div>
        <div class="tip-item">
            <h3>Price by value</h3>
            <p>Tie pricing to support level and performance promises, not only storage numbers.</p>
        </div>
        <div class="tip-item">
            <h3>Status usage</h3>
            <p>Keep inactive plans hidden from new sales while preserving old customer subscriptions.</p>
        </div>
        <div class="tip-item">
            <h3>Description quality</h3>
            <p>Use concise feature bullets clients can compare quickly during checkout.</p>
        </div>
    </div>
@endsection
