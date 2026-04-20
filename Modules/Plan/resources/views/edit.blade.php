@extends('layouts.panel')

@section('title', 'Edit Plan - Xenweet Panel')

@section('content')
    <section class="form-card">
        <h1>Edit Hosting Plan</h1>
        <p class="subtle">Update package limits, price, and availability.</p>

        @if ($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('plan.update', $plan) }}" class="host-form">
            @csrf
            @method('PUT')

            <label for="name">Plan Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $plan->name) }}" required>

            <label for="monthly_price">Monthly Price (USD)</label>
            <input id="monthly_price" name="monthly_price" type="number" min="0" step="0.01" value="{{ old('monthly_price', $plan->monthly_price) }}" required>

            <label for="disk_limit_mb">Disk Limit (MB)</label>
            <input id="disk_limit_mb" name="disk_limit_mb" type="number" min="0" value="{{ old('disk_limit_mb', $plan->disk_limit_mb) }}" required>

            <label for="bandwidth_gb">Bandwidth (GB)</label>
            <input id="bandwidth_gb" name="bandwidth_gb" type="number" min="0" value="{{ old('bandwidth_gb', $plan->bandwidth_gb) }}" required>

            <label for="max_domains">Max Domains</label>
            <input id="max_domains" name="max_domains" type="number" min="1" value="{{ old('max_domains', $plan->max_domains) }}" required>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" @selected(old('status', $plan->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $plan->status) === 'inactive')>Inactive</option>
            </select>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4">{{ old('description', $plan->description) }}</textarea>

            <div class="form-actions">
                <a href="{{ route('plan.index') }}" class="btn-secondary link-button">Cancel</a>
                <button type="submit" class="btn-primary">Update Plan</button>
            </div>
        </form>
    </section>
@endsection
