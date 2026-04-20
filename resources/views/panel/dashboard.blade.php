@extends('layouts.panel')

@section('title', 'Xenweet Hosting Panel')

@section('content')
    <header class="topbar">
        <div>
            <p class="eyebrow">Hosting Panel</p>
            <h1>Hosting List</h1>
            <p class="subtle">Last sync: {{ $lastSync }}</p>
        </div>

        <div class="topbar-actions">
            <a class="btn-primary compact" href="{{ route('hosts.create') }}">+ Create Host</a>
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
                    <p class="label">Panel User</p>
                    <strong>{{ $hosting->panel_username }}</strong>
                </div>
                <div>
                    <p class="label">Panel Pass</p>
                    <strong>{{ $hosting->panel_password }}</strong>
                </div>
                <div>
                    <p class="label">PHP</p>
                    <strong>{{ $hosting->php_version }}</strong>
                </div>
                <div>
                    <p class="label">Status</p>
                    <span class="status online">{{ $hosting->status }}</span>
                </div>
                <div>
                    <p class="label">Disk Usage</p>
                    <strong>{{ $hosting->disk_usage_mb }} MB</strong>
                </div>
                <div>
                    <p class="label">CLI Provision</p>
                    <strong>{{ ucfirst($hosting->provision_status) }}</strong>
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
@endsection
