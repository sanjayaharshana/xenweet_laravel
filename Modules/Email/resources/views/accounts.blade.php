@extends('layouts.host')

@section('title', 'Email Accounts - ' . $hosting->domain)

@section('content')
@php
    $activeTab = in_array($tab ?? 'accounts', ['accounts', 'forwarders', 'autoresponders', 'filters', 'usage'], true)
        ? ($tab ?? 'accounts')
        : 'accounts';
    $zeeBrooMailEnabled = \Nwidart\Modules\Facades\Module::isEnabled('ZeeBrooMail')
        && \Illuminate\Support\Facades\Route::has('hosts.zeebroo-mail.index');
@endphp
<div class="host-panel-scope managedb-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Email</p>
            <h1>Email Manager</h1>
            <p class="subtle">cPanel-like email tools for this host with local mail stack integration.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>
    <div class="server-card" style="margin-bottom:1rem;border-left:4px solid #16a34a;">
        <p class="subtle" style="margin:0;">
            Local mail stack mode is enabled. Mailboxes are provisioned for IMAP/SMTP authentication using
            <strong>username@domain</strong>.
        </p>
    </div>

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

    <section class="server-card" style="margin-bottom:1rem;">
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
            <a class="btn-secondary" href="{{ route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'accounts']) }}">Accounts</a>
            <a class="btn-secondary" href="{{ route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'forwarders']) }}">Forwarders</a>
            <a class="btn-secondary" href="{{ route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'autoresponders']) }}">Auto Responders</a>
            <a class="btn-secondary" href="{{ route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'filters']) }}">Filters</a>
            <a class="btn-secondary" href="{{ route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'usage']) }}">Usage</a>
        </div>
    </section>

    @if ($activeTab === 'accounts')
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
                    <span>Mailbox</span>
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
                        <span>
                            @if ($zeeBrooMailEnabled)
                                <a
                                    class="btn-secondary"
                                    href="{{ route('hosts.zeebroo-mail.index', ['hosting' => $hosting, 'account_id' => $account->id, 'folder' => 'INBOX']) }}"
                                >
                                    Open ZeeBroo Mail
                                </a>
                            @else
                                <span class="subtle">Unavailable</span>
                            @endif
                        </span>
                        <span>
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
    @endif

    @if ($activeTab === 'forwarders')
    <section class="server-card">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Create forwarder</h2>
        <form method="post" action="{{ route('hosts.email.forwarders.store', $hosting) }}" class="managedb-form">
            @csrf
            <input type="hidden" name="_context" value="create_forwarder">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0.75rem;">
                <label>
                    <span class="subtle">Source mailbox</span>
                    <input type="email" name="source_email" value="{{ old('source_email') }}" placeholder="info@{{ $defaultDomain }}" required>
                </label>
                <label>
                    <span class="subtle">Destination email</span>
                    <input type="email" name="destination_email" value="{{ old('destination_email') }}" placeholder="admin@example.com" required>
                </label>
            </div>
            <div style="margin-top:0.8rem;">
                <button type="submit" class="btn-primary">Create Forwarder</button>
            </div>
        </form>
    </section>
    <section class="server-card" style="margin-top:1rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Existing forwarders</h2>
        @if ($forwarders->isEmpty())
            <p class="subtle">No forwarders configured yet.</p>
        @else
            <div class="file-table" style="margin-top:0.65rem;">
                <div class="file-row file-row-head"><span>Source</span><span>Destination</span><span>Status</span><span>Action</span></div>
                @foreach ($forwarders as $forwarder)
                    <div class="file-row">
                        <span>{{ $forwarder->source_email }}</span>
                        <span>{{ $forwarder->destination_email }}</span>
                        <span>{{ ucfirst((string) $forwarder->status) }}</span>
                        <span>
                            <form method="post" action="{{ route('hosts.email.forwarders.destroy', ['hosting' => $hosting, 'forwarder' => $forwarder->id]) }}" onsubmit="return confirm('Delete this forwarder?');">
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
    @endif

    @if ($activeTab === 'autoresponders')
    <section class="server-card">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Create auto responder</h2>
        <form method="post" action="{{ route('hosts.email.autoresponders.store', $hosting) }}" class="managedb-form">
            @csrf
            <input type="hidden" name="_context" value="create_autoresponder">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0.75rem;">
                <label>
                    <span class="subtle">Mailbox email</span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="hello@{{ $defaultDomain }}" required>
                </label>
                <label>
                    <span class="subtle">Subject</span>
                    <input type="text" name="subject" value="{{ old('subject') }}" required>
                </label>
                <label style="grid-column:1/-1;">
                    <span class="subtle">Message</span>
                    <textarea name="body" rows="4" required>{{ old('body') }}</textarea>
                </label>
                <label>
                    <span class="subtle">Enable now</span>
                    <select name="enabled">
                        <option value="0">No</option>
                        <option value="1" @selected(old('enabled') === '1')>Yes</option>
                    </select>
                </label>
            </div>
            <div style="margin-top:0.8rem;">
                <button type="submit" class="btn-primary">Save Auto Responder</button>
            </div>
        </form>
    </section>
    <section class="server-card" style="margin-top:1rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Existing auto responders</h2>
        @if ($autoresponders->isEmpty())
            <p class="subtle">No auto responders configured yet.</p>
        @else
            <div class="file-table" style="margin-top:0.65rem;">
                <div class="file-row file-row-head"><span>Email</span><span>Subject</span><span>Status</span><span>Action</span></div>
                @foreach ($autoresponders as $autoresponder)
                    <div class="file-row">
                        <span>{{ $autoresponder->email }}</span>
                        <span>{{ $autoresponder->subject }}</span>
                        <span>{{ $autoresponder->enabled ? 'Enabled' : 'Disabled' }}</span>
                        <span>
                            <form method="post" action="{{ route('hosts.email.autoresponders.destroy', ['hosting' => $hosting, 'autoresponder' => $autoresponder->id]) }}" onsubmit="return confirm('Delete this auto responder?');">
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
    @endif

    @if ($activeTab === 'filters')
    <section class="server-card">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Create filter</h2>
        <form method="post" action="{{ route('hosts.email.filters.store', $hosting) }}" class="managedb-form">
            @csrf
            <input type="hidden" name="_context" value="create_filter">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0.75rem;">
                <label>
                    <span class="subtle">Scope</span>
                    <select name="scope">
                        <option value="global" @selected(old('scope') === 'global')>Global</option>
                        <option value="mailbox" @selected(old('scope') === 'mailbox')>Mailbox</option>
                    </select>
                </label>
                <label>
                    <span class="subtle">Mailbox (for mailbox scope)</span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="support@{{ $defaultDomain }}">
                </label>
                <label>
                    <span class="subtle">Filter name</span>
                    <input type="text" name="rule_name" value="{{ old('rule_name') }}" required>
                </label>
                <label>
                    <span class="subtle">Condition</span>
                    <select name="condition_type">
                        <option value="contains">Contains</option>
                        <option value="equals">Equals</option>
                    </select>
                </label>
                <label>
                    <span class="subtle">Condition value</span>
                    <input type="text" name="condition_value" value="{{ old('condition_value') }}" required>
                </label>
                <label>
                    <span class="subtle">Action</span>
                    <select name="action_type">
                        <option value="move_to_folder">Move to folder</option>
                        <option value="discard">Discard</option>
                        <option value="mark_read">Mark as read</option>
                    </select>
                </label>
                <label>
                    <span class="subtle">Action value</span>
                    <input type="text" name="action_value" value="{{ old('action_value') }}" placeholder="INBOX/Support">
                </label>
            </div>
            <div style="margin-top:0.8rem;">
                <button type="submit" class="btn-primary">Save Filter</button>
            </div>
        </form>
    </section>
    <section class="server-card" style="margin-top:1rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Existing filters</h2>
        @if ($filters->isEmpty())
            <p class="subtle">No filters configured yet.</p>
        @else
            <div class="file-table" style="margin-top:0.65rem;">
                <div class="file-row file-row-head"><span>Name</span><span>Scope</span><span>Rule</span><span>Action</span></div>
                @foreach ($filters as $filter)
                    <div class="file-row">
                        <span>{{ $filter->rule_name }}</span>
                        <span>{{ $filter->scope === 'mailbox' ? 'Mailbox: '.$filter->email : 'Global' }}</span>
                        <span>{{ $filter->condition_type }} "{{ $filter->condition_value }}"</span>
                        <span>
                            <form method="post" action="{{ route('hosts.email.filters.destroy', ['hosting' => $hosting, 'filter' => $filter->id]) }}" onsubmit="return confirm('Delete this filter?');">
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
    @endif

    @if ($activeTab === 'usage')
    <section class="server-card">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Mailbox usage</h2>
        @if ($accounts->isEmpty())
            <p class="subtle">No accounts found. Create mailboxes first to track quota usage.</p>
        @else
            <div class="file-table" style="margin-top:0.65rem;">
                <div class="file-row file-row-head"><span>Email</span><span>Quota</span><span>Stored</span><span>Status</span></div>
                @foreach ($accounts as $account)
                    <div class="file-row">
                        <span>{{ $account->local_part }}@{{ $account->domain }}</span>
                        <span>{{ number_format((int) $account->quota_mb) }} MB</span>
                        <span>0 MB (DB-only mode)</span>
                        <span>{{ ucfirst((string) $account->status) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
    @endif

</div>
@endsection
