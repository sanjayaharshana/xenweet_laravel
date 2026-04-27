@extends('layouts.host')

@section('title', '2FA Authentication - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Security</p>
            <h1>2FA Authentication</h1>
            <p class="subtle">Protect host panel login with authenticator-based OTP and one-time recovery codes.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    @if (session('success'))
        <div class="file-manager-flash file-manager-flash--success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="file-manager-flash file-manager-flash--error" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="file-manager-flash file-manager-flash--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="server-card">
        @if (! $enabled)
            <h2>Set up authenticator app</h2>
            <p class="subtle">Add this host account in Google Authenticator, 1Password, Authy, or any TOTP app.</p>
            <div style="margin:0.6rem 0 0.75rem">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($setupUri ?? '') }}"
                    alt="2FA setup QR code"
                    width="220"
                    height="220"
                    style="display:block;max-width:100%;height:auto;border-radius:10px;border:1px solid rgba(255,255,255,0.15);background:#fff;padding:0.35rem"
                >
            </div>
            <div class="meta meta--sidebar" style="margin-top:0.5rem">
                <div><span>Manual key</span><strong style="font-family:monospace">{{ $setupSecret }}</strong></div>
                <div><span>OTP URI</span><strong style="font-family:monospace;word-break:break-all">{{ $setupUri }}</strong></div>
            </div>

            <form method="POST" action="{{ route('hosts.security.2fa.update', $hosting) }}" class="auth-form" style="margin-top:0.8rem">
                @csrf
                <input type="hidden" name="action" value="enable">
                <label for="code">Authenticator code (6 digits)</label>
                <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required>
                <button type="submit" class="btn-primary">Enable 2FA</button>
            </form>
        @else
            <h2>2FA is enabled</h2>
            <p class="subtle">Users must provide a valid OTP code after panel username/password for this host.</p>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem">
                <form method="POST" action="{{ route('hosts.security.2fa.update', $hosting) }}">
                    @csrf
                    <input type="hidden" name="action" value="regenerate_recovery">
                    <button type="submit" class="btn-secondary">Regenerate recovery codes</button>
                </form>
                <form method="POST" action="{{ route('hosts.security.2fa.update', $hosting) }}" onsubmit="return confirm('Disable 2FA for this host panel login?');">
                    @csrf
                    <input type="hidden" name="action" value="disable">
                    <button type="submit" class="btn-secondary">Disable 2FA</button>
                </form>
            </div>
        @endif
    </div>

    @if (is_array($recoveryCodes) && count($recoveryCodes) > 0)
        <div class="server-card" style="margin-top:0.75rem">
            <h2>Recovery codes</h2>
            <p class="subtle">Store these in a safe place. Each code works once. These are shown only once.</p>
            <ul style="display:grid;grid-template-columns:repeat(auto-fit,minmax(12rem,1fr));gap:0.35rem;padding-left:1rem">
                @foreach ($recoveryCodes as $code)
                    <li><code>{{ $code }}</code></li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
