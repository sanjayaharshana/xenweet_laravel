<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Host - Xenweet Panel</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body class="dashboard-body">
    <nav class="main-navbar">
        <div class="nav-inner">
            <a href="{{ route('panel') }}" class="brand">
                <span class="brand-dot"></span>
                Xenweet Panel
            </a>

            <div class="nav-links">
                <a href="{{ route('panel') }}">Dashboard</a>
                <a href="{{ route('hosts.create') }}" class="active">Create Host</a>
            </div>

            <div class="nav-user">
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-secondary" type="submit">Logout</button>
                </form>
            </div>
        </div>
    </nav>

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

            <label for="server_ip">Server IP</label>
            <input id="server_ip" name="server_ip" type="text" value="{{ old('server_ip') }}" placeholder="192.168.1.10" required>

            <label for="plan">Plan</label>
            <input id="plan" name="plan" type="text" value="{{ old('plan', 'Starter') }}" required>

            <label for="php_version">PHP Version</label>
            <input id="php_version" name="php_version" type="text" value="{{ old('php_version', '8.3') }}" required>

            <label for="disk_usage_mb">Disk Usage (MB)</label>
            <input id="disk_usage_mb" name="disk_usage_mb" type="number" min="0" value="{{ old('disk_usage_mb', 0) }}" required>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
                <option value="Suspended" @selected(old('status') === 'Suspended')>Suspended</option>
                <option value="Pending" @selected(old('status') === 'Pending')>Pending</option>
            </select>

            <div class="form-actions">
                <a href="{{ route('panel') }}" class="btn-secondary link-button">Cancel</a>
                <button type="submit" class="btn-primary">Create Host</button>
            </div>
        </form>
    </section>
</body>
</html>
