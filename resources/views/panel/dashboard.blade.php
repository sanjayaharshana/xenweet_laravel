<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xenweet Hosting Panel</title>
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
                <a href="{{ route('panel') }}" class="active">Dashboard</a>
                <a href="{{ route('hosts.create') }}">Create Host</a>
                <a href="#">Databases</a>
                <a href="#">Security</a>
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

    <header class="topbar">
        <div>
            <p class="eyebrow">Hosting Panel</p>
            <h1>Hosting List</h1>
            <p class="subtle">Last sync: {{ $lastSync }}</p>
        </div>

        <div class="topbar-actions">
            <a class="btn-primary compact" href="{{ route('hosts.create') }}">+ Create Host</a>
            <div class="user-chip">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->email }}</span>
            </div>
        </div>
    </header>

    @if (session('success'))
        <section class="flash-success">
            {{ session('success') }}
        </section>
    @endif

    <main class="hosting-list-shell">
        @forelse ($hostings as $hosting)
            <article class="hosting-row">
                <div>
                    <p class="label">Domain</p>
                    <strong>{{ $hosting->domain }}</strong>
                </div>
                <div>
                    <p class="label">Server IP</p>
                    <strong>{{ $hosting->server_ip }}</strong>
                </div>
                <div>
                    <p class="label">Plan</p>
                    <strong>{{ $hosting->plan }}</strong>
                </div>
                <div>
                    <p class="label">PHP</p>
                    <strong>{{ $hosting->php_version }}</strong>
                </div>
                <div>
                    <p class="label">Disk Usage</p>
                    <strong>{{ $hosting->disk_usage_mb }} MB</strong>
                </div>
                <div>
                    <p class="label">Status</p>
                    <span class="status online">{{ $hosting->status }}</span>
                </div>
            </article>
        @empty
            <article class="server-card empty-state">
                <h2>No hosting accounts yet</h2>
                <p>Create your first host to show it in this list.</p>
                <a class="btn-primary compact" href="{{ route('hosts.create') }}">Create Host</a>
            </article>
        @endforelse
    </main>
</body>
</html>
