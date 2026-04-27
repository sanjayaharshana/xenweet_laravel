@extends('layouts.host')

@section('title', 'Email Accounts - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope managedb-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Email</p>
            <h1>Email Accounts</h1>
            <p class="subtle">Create and manage mailbox users for this hosting account.</p>
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
    @if (session('error'))
        <div class="server-card" style="border-left:4px solid var(--danger-border, #dc2626); margin-bottom:1rem;">
            <p class="subtle" style="margin:0;">{{ session('error') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="server-card" style="border-left:4px solid var(--danger-border, #dc2626); margin-bottom:1rem;">
            <p class="subtle" style="margin:0;">{{ $errors->first() }}</p>
        </div>
    @endif

    <section class="server-card">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Create account</h2>
        <form method="post" action="{{ route('hosts.email.accounts.store', $hosting) }}" class="managedb-form">
            @csrf
            <input type="hidden" name="_context" value="create_email">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0.75rem;">
                <label>
                    <span class="subtle">Email username</span>
                    <input type="text" name="local_part" value="{{ old('local_part') }}" required>
                </label>
                <label>
                    <span class="subtle">Domain</span>
                    <input type="text" name="domain" value="{{ old('domain', $defaultDomain) }}" required>
                </label>
                <label>
                    <span class="subtle">Password</span>
                    <input type="password" name="password" required>
                </label>
                <label>
                    <span class="subtle">Quota (MB)</span>
                    <input type="number" name="quota_mb" min="50" value="{{ old('quota_mb', 1024) }}">
                </label>
            </div>
            <div style="margin-top:0.8rem;">
                <button type="submit" class="btn-primary">Create Email Account</button>
            </div>
        </form>
    </section>

    <section class="server-card" style="margin-top:1rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Existing accounts</h2>
        @if ($accounts->isEmpty())
            <p class="subtle">No email accounts created yet.</p>
        @else
            <div class="file-table" style="margin-top:0.65rem;">
                <div class="file-row file-row-head">
                    <span>Email</span>
                    <span>Quota</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>
                @foreach ($accounts as $account)
                    <div class="file-row">
                        @php
                            $rawLocal = (string) $account->local_part;
                            $rawDomain = (string) $account->domain;
                            $local = preg_replace('/\s+/', '', str_replace(['{{', '}}', '$account->domain'], '', $rawLocal));
                            $local = preg_replace('/[^a-zA-Z0-9._%+\-]/', '', (string) $local) ?? '';
                            $domain = \App\Models\Hosting::normalizeDomainName($rawDomain);
                            $displayEmail = trim($local) !== '' && trim($domain) !== '' ? $local.'@'.$domain : (trim($local) !== '' ? $local : ($rawLocal.'@'.$rawDomain));
                        @endphp
                        <span>{{ $displayEmail }}</span>
                        <span>{{ number_format((int) $account->quota_mb) }} MB</span>
                        <span>{{ ucfirst((string) $account->status) }}</span>
                        <span style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                            <a class="btn-primary" target="_blank" rel="noopener" href="{{ route('hosts.email.accounts.webmail-login', ['hosting' => $hosting, 'emailAccount' => $account->id]) }}">
                                Open Webmail
                            </a>
                            <form method="post" action="{{ route('hosts.email.accounts.destroy', ['hosting' => $hosting, 'emailAccount' => $account->id]) }}" onsubmit="return confirm('Delete this email account?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-secondary">Delete</button>
                            </form>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
