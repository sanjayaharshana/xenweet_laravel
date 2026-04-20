@extends('layouts.panel')

@section('title', 'Xenweet Hosting Panel')

@section('content')
<div class="dashboard-hosting-page">
    <header class="topbar dashboard-topbar">
        <div>
            <p class="eyebrow">Hosting Panel</p>
            <h1>Hosting List</h1>
            <p class="subtle">Last sync: {{ $lastSync }}</p>
        </div>

        <div class="topbar-actions">
            <a class="btn-primary btn-create-host" href="{{ route('hosts.create') }}">
                <span class="btn-create-host__icon" aria-hidden="true">+</span>
                Create host
            </a>
        </div>
    </header>

    @if (session('success'))
        <section class="flash-success">
            {{ session('success') }}
        </section>
    @endif

    <main class="hosting-list-shell">
        @forelse ($hostings as $hosting)
            <article class="hosting-card">
                <div class="hosting-card__header">
                    <div class="hosting-card__title-group">
                        <p class="label hosting-card__label">Domain</p>
                        <p class="hosting-card__domain">{{ $hosting->domain }}</p>
                    </div>
                    <div class="hosting-card__badges">
                        <span class="status-pill status-pill--{{ \Illuminate\Support\Str::slug($hosting->status) }}">{{ $hosting->status }}</span>
                        @if ($hosting->provision_status)
                            <span class="provision-pill provision-pill--{{ $hosting->provision_status }}">{{ ucfirst($hosting->provision_status) }}</span>
                        @endif
                    </div>
                </div>

                <div class="hosting-card__grid">
                    <div class="hosting-card__cell">
                        <p class="label">Server IP</p>
                        <p class="hosting-card__value">{{ $hosting->server_ip }}</p>
                    </div>
                    <div class="hosting-card__cell">
                        <p class="label">Plan</p>
                        <p class="hosting-card__value">{{ $hosting->plan }}</p>
                    </div>
                    <div class="hosting-card__cell">
                        <p class="label">Panel user</p>
                        <p class="hosting-card__value">{{ $hosting->panel_username }}</p>
                    </div>
                    <div class="hosting-card__cell">
                        <p class="label">Panel password</p>
                        <p class="hosting-card__value hosting-card__value--mono">{{ $hosting->panel_password }}</p>
                    </div>
                    <div class="hosting-card__cell">
                        <p class="label">PHP</p>
                        <p class="hosting-card__value">{{ $hosting->php_version }}</p>
                    </div>
                    <div class="hosting-card__cell">
                        <p class="label">Disk usage</p>
                        <p class="hosting-card__value">{{ number_format($hosting->disk_usage_mb) }} MB</p>
                    </div>
                </div>

                <div class="hosting-card__footer">
                    <div class="hosting-card__actions">
                        <a class="hosting-card__btn btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Open Panel</a>
                        <a class="hosting-card__btn btn-secondary" href="{{ $hosting->publicSiteUrl() }}" target="_blank" rel="noopener noreferrer">Open Site</a>
                        <a class="hosting-card__btn btn-secondary hosting-card__btn--muted" href="http://{{ $hosting->server_ip }}" target="_blank" rel="noopener noreferrer" title="Direct IP (default vhost)">Open IP</a>
                        <form method="POST" action="{{ route('hosts.destroy', $hosting) }}" class="hosting-card__remove-form" onsubmit="return confirm('Remove this hosting account? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="hosting-card__btn btn-secondary danger-btn">Remove</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <article class="server-card empty-state hosting-empty">
                <h2>No hosting accounts yet</h2>
                <p>Create your first host to show it in this list.</p>
                <a class="btn-primary btn-create-host" href="{{ route('hosts.create') }}">
                    <span class="btn-create-host__icon" aria-hidden="true">+</span>
                    Create host
                </a>
            </article>
        @endforelse
    </main>
</div>
@endsection
