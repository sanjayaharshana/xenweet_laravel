@extends('layouts.panel')

@section('title', 'Create Host - Xenweet Panel')

@section('content')
    <section class="form-card">
        <h1>Create Hosting Account</h1>
        <p class="subtle">Add a new hosted domain to your panel list.</p>

        @if ($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('hosts.store') }}" class="host-form">
            @csrf

            <label for="domain">Domain</label>
            <input id="domain" name="domain" type="text" value="{{ old('domain') }}" placeholder="example.com" required>
            <p class="field-hint">Use a valid root domain like <code>example.com</code>.</p>

            <label for="server_ip">Server IP</label>
            <div class="input-with-action">
                <input id="server_ip" name="server_ip" type="text" value="{{ old('server_ip') }}" placeholder="192.168.1.10" required>
                <button
                    type="button"
                    class="btn-secondary compact-btn"
                    data-fill-server-ip="{{ $currentServerIp }}"
                    onclick="document.getElementById('server_ip').value = this.dataset.fillServerIp;"
                >
                    Use Current Server IP
                </button>
            </div>
            <p class="field-hint">Enter the IPv4 or IPv6 address where the host is deployed.</p>

            <label for="plan">Plan</label>
            <select id="plan" name="plan" required @disabled($plans->isEmpty())>
                <option value="">Select hosting plan</option>
                @foreach ($plans as $plan)
                    <option value="{{ $plan->name }}" @selected(old('plan') === $plan->name)>{{ $plan->name }}</option>
                @endforeach
            </select>
            <p class="field-hint">
                Choose an active plan from Plan module.
                @if ($plans->isEmpty())
                    No active plans found. Create one in <a href="{{ route('plan.create') }}">Plans</a> first.
                @endif
            </p>

            <label for="panel_username">Panel Username</label>
            <input id="panel_username" name="panel_username" type="text" value="{{ old('panel_username') }}" required>
            <p class="field-hint">Use a unique login username for this hosting account.</p>

            <label for="panel_password">Panel Password</label>
            <input id="panel_password" name="panel_password" type="password" value="{{ old('panel_password') }}" required>
            <p class="field-hint">Use a strong password with letters, numbers, and symbols.</p>

            <label for="php_version">PHP Version</label>
            <input id="php_version" name="php_version" type="text" value="{{ old('php_version', '8.3') }}" required>
            <p class="field-hint">Choose the runtime version required by the hosted application.</p>

            <label for="disk_usage_mb">Disk Usage (MB)</label>
            <input id="disk_usage_mb" name="disk_usage_mb" type="number" min="0" value="{{ old('disk_usage_mb', 0) }}" required>
            <p class="field-hint">Current or expected storage usage in MB for quota tracking.</p>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
                <option value="Suspended" @selected(old('status') === 'Suspended')>Suspended</option>
                <option value="Pending" @selected(old('status') === 'Pending')>Pending</option>
            </select>
            <p class="field-hint">Active is live, Pending is setup mode, and Suspended is disabled.</p>

            <div class="form-actions">
                <a href="{{ route('panel') }}" class="btn-secondary link-button">Cancel</a>
                <button type="submit" class="btn-primary">Create Host</button>
            </div>
        </form>
    </section>
@endsection

@section('right_sidebar')
    <div class="tips-panel">
        <h2>Tips & Tricks</h2>
        <p class="subtle">Best practices for structuring and scaling hosting accounts.</p>

        <div class="tip-item">
            <h3>How to split hosting plans</h3>
            <p>Create clear tiers like Starter, Pro, and Business. Split by disk quota, CPU, and support priority so customers choose faster.</p>
        </div>
        <div class="tip-item">
            <h3>Separate by workload</h3>
            <p>Group static sites, CMS apps, and heavy e-commerce sites into different plans to prevent noisy-neighbor issues.</p>
        </div>
        <div class="tip-item">
            <h3>Username convention</h3>
            <p>Use predictable usernames like <code>clientname01</code> to simplify support, backups, and migrations.</p>
        </div>
        <div class="tip-item">
            <h3>Password policy</h3>
            <p>Require minimum 12+ characters and rotate credentials when handing over accounts to client teams.</p>
        </div>
        <div class="tip-item">
            <h3>Resource monitoring</h3>
            <p>Track disk usage trends monthly and upsell to the next plan before accounts hit limits.</p>
        </div>
        <div class="tip-item">
            <h3>Status workflow</h3>
            <p>Use Pending during setup, switch to Active after DNS and SSL are live, and use Suspended only for policy/billing actions.</p>
        </div>
    </div>
@endsection
