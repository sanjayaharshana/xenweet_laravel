@extends('layouts.host')

@section('title', 'Hotlink Protection - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope managedb-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Security</p>
            <h1>Hotlink Protection</h1>
            <p class="subtle">Block third-party websites from embedding your static assets directly.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    @if (session('success'))
        <div class="server-card" style="border-left:4px solid var(--success-border, #16a34a); margin-bottom:1rem;">
            <p class="subtle" style="margin:0;">{{ session('success') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="server-card" style="border-left:4px solid var(--danger-border, #dc2626); margin-bottom:1rem;">
            <p class="subtle" style="margin:0;">{{ $errors->first() }}</p>
        </div>
    @endif

    <section class="server-card">
        <form method="post" action="{{ route('hosts.hotlink-protection.update', $hosting) }}" class="managedb-form">
            @csrf

            <label style="display:flex; align-items:center; gap:0.5rem;">
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $config['enabled']))>
                <strong>Enable hotlink protection</strong>
            </label>

            <label style="display:flex; align-items:center; gap:0.5rem; margin-top:0.6rem;">
                <input type="checkbox" name="allow_direct_requests" value="1" @checked(old('allow_direct_requests', $config['allowDirect']))>
                <span>Allow empty/no-referrer requests (direct open, some privacy browsers)</span>
            </label>

            <label for="allowed_domains_raw" style="display:block; margin-top:0.9rem;">Allowed domains (one per line or comma-separated)</label>
            <textarea id="allowed_domains_raw" name="allowed_domains_raw" rows="6" style="width:100%;max-width:58rem;">{{ old('allowed_domains_raw', implode("\n", $config['allowedDomains'])) }}</textarea>
            <p class="subtle" style="margin-top:0.35rem;">Include your main domain and any CDN or subdomain that may embed these assets.</p>

            <label for="blocked_extensions_raw" style="display:block; margin-top:0.9rem;">Protected extensions (one per line or comma-separated)</label>
            <textarea id="blocked_extensions_raw" name="blocked_extensions_raw" rows="4" style="width:100%;max-width:58rem;">{{ old('blocked_extensions_raw', implode("\n", $config['blockedExtensions'])) }}</textarea>
            <p class="subtle" style="margin-top:0.35rem;">Example: jpg, jpeg, png, webp, gif, svg, mp4</p>

            <div style="margin-top:0.95rem; display:flex; gap:0.6rem;">
                <button type="submit" class="btn-primary">Save settings</button>
                <a class="btn-secondary" href="{{ route('hosts.hotlink-protection', $hosting) }}">Reset</a>
            </div>
        </form>
    </section>

    <section class="server-card" style="margin-top:1rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Nginx snippet preview</h2>
        <p class="subtle">This is generated from your settings. Paste into your host-level Nginx include/server config.</p>
        <pre style="white-space:pre-wrap;overflow:auto;"><code>{{ $config['nginxSnippet'] }}</code></pre>
    </section>
</div>
@endsection
