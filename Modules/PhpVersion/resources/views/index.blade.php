@extends('layouts.host')

@section('title', 'PHP version - Xenweet')

@section('content')
<div class="host-panel-scope managedb-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Software</p>
            <h1>PHP version</h1>
            <p class="subtle">Applies to this hosting account’s <strong>website only</strong> (Nginx &rarr; PHP-FPM). SSH and CLI commands may use a different <code>php</code> binary on the server.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    @if (session('success'))
        <div class="server-card" style="border-left:4px solid var(--success-border, #16a34a); margin-bottom:1rem;">
            <p class="subtle" style="margin:0; color: inherit;">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="server-card" style="border-left:4px solid var(--danger-border, #dc2626); margin-bottom:1rem;">
            <p class="subtle" style="margin:0; color: inherit;">{{ session('error') }}</p>
        </div>
    @endif

    <section class="server-card">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Web PHP for {{ $hosting->siteHost() }}</h2>
        <p class="subtle" style="margin-top:0.5rem;">Selected pool targets this socket (when not overridden in <code>config/hosting_provision.php</code>):</p>
        <p><code>{{ $fpmSocket }}</code></p>
        <p class="subtle" style="margin-top:0.5rem;">Nginx vhost regeneration: <strong>{{ $vhostEnabled ? 'enabled' : 'disabled' }}</strong>
            @unless ($vhostEnabled)
                (set <code>HOSTING_VHOST_ENABLED=true</code> to apply the <strong>HTTP-only</strong> vhost on save, when you are not using SSL from this panel.)
            @endunless
        </p>
        <p class="subtle" style="margin-top:0.75rem; margin-bottom:0;">If you use <strong>HTTPS</strong> (PEM files under this account&rsquo;s <code>ssl/</code> from Auto SSL or <strong>Install certificate</strong>), saving here re-applies the <strong>full</strong> Nginx config (port 80 + 443) so the PHP pool matches and HTTPS keeps working. Only plain-HTTP sites use the HTTP-only vhost script.</p>
    </section>

    <section class="server-card" style="margin-top:1.25rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Change version</h2>
        <form class="managedb-form" method="post" action="{{ route('hosts.php-version.update', $hosting) }}">
            @csrf
            <label for="php_version" class="subtle">Version</label>
            <div style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center; margin-top:0.35rem;">
                <select id="php_version" name="php_version" required style="min-width:12rem;">
                    @foreach ($allowedVersions as $v)
                        <option value="{{ $v }}" @selected($hosting->php_version === $v)>PHP {{ $v }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary">Save &amp; apply to web vhost</button>
            </div>
            @error('php_version')
                <p class="subtle" style="color: var(--danger-text, #b91c1c); margin-top:0.5rem; margin-bottom:0;">{{ $message }}</p>
            @enderror
            <p class="subtle" style="margin-top:0.75rem; margin-bottom:0;">The server must run a <code>phpX.Y-fpm</code> pool and socket for the version you select (e.g. Debian <code>php8.2-fpm</code>). A missing pool typically returns HTTP 502.</p>
        </form>
    </section>
</div>
@endsection
