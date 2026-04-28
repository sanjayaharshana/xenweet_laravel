@extends('layouts.host')

@section('title', 'ZeeBroo Mail - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope zeebroo-mail">
    <style>
        .zeebroo-mail { --zb-bg:#f6f8fc; --zb-surface:#fff; --zb-border:#dde3ea; --zb-text:#202124; --zb-subtle:#5f6368; --zb-primary:#1a73e8; --zb-primary-soft:#e8f0fe; }
        .zeebroo-mail .zb-frame { background:var(--zb-bg); border:1px solid var(--zb-border); border-radius:14px; overflow:hidden; }
        .zeebroo-mail .zb-shell { display:grid; grid-template-columns:220px 360px 1fr; min-height:680px; }
        .zeebroo-mail .zb-side, .zeebroo-mail .zb-list, .zeebroo-mail .zb-read { background:var(--zb-surface); }
        .zeebroo-mail .zb-side { border-right:1px solid var(--zb-border); padding:0.85rem 0.7rem; }
        .zeebroo-mail .zb-list { border-right:1px solid var(--zb-border); display:flex; flex-direction:column; min-width:0; }
        .zeebroo-mail .zb-read { display:flex; flex-direction:column; min-width:0; }
        .zeebroo-mail .zb-user { display:flex; align-items:center; justify-content:space-between; gap:0.4rem; border-radius:10px; padding:0.52rem 0.55rem; background:#f1f3f4; margin-bottom:0.8rem; color:var(--zb-text); font-size:0.84rem; font-weight:600; }
        .zeebroo-mail .zb-section-title { margin:0.85rem 0 0.42rem; color:var(--zb-subtle); font-size:0.75rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:700; }
        .zeebroo-mail .zb-folder-list { display:flex; flex-direction:column; gap:0.22rem; }
        .zeebroo-mail .zb-folder-item { display:flex; align-items:center; justify-content:space-between; gap:0.5rem; border-radius:999px; padding:0.48rem 0.74rem; color:var(--zb-text); text-decoration:none; font-size:0.9rem; border:1px solid transparent; }
        .zeebroo-mail .zb-folder-item:hover { background:#f1f3f4; }
        .zeebroo-mail .zb-folder-item.active { background:var(--zb-primary-soft); color:var(--zb-primary); font-weight:700; border-color:#d3e3fd; }
        .zeebroo-mail .zb-list-top { padding:0.72rem; border-bottom:1px solid var(--zb-border); display:grid; gap:0.55rem; }
        .zeebroo-mail .zb-search { border:1px solid var(--zb-border); background:#f1f3f4; border-radius:999px; padding:0.52rem 0.78rem; color:var(--zb-subtle); font-size:0.9rem; }
        .zeebroo-mail .zb-actions { display:flex; gap:0.4rem; flex-wrap:wrap; }
        .zeebroo-mail .zb-chip { border:1px solid var(--zb-border); background:#fff; color:var(--zb-subtle); font-size:0.78rem; padding:0.2rem 0.48rem; border-radius:999px; }
        .zeebroo-mail .zb-message-list { overflow:auto; flex:1; }
        .zeebroo-mail .zb-message-item { display:block; text-decoration:none; color:var(--zb-text); border-bottom:1px solid var(--zb-border); padding:0.62rem 0.72rem; background:#fff; }
        .zeebroo-mail .zb-message-item:hover { background:#f8fafd; }
        .zeebroo-mail .zb-message-item.active { background:var(--zb-primary-soft); }
        .zeebroo-mail .zb-message-line { display:flex; align-items:center; justify-content:space-between; gap:0.5rem; font-size:0.82rem; }
        .zeebroo-mail .zb-from { font-weight:700; color:#1f2937; }
        .zeebroo-mail .zb-date { color:var(--zb-subtle); white-space:nowrap; }
        .zeebroo-mail .zb-subject { margin-top:0.18rem; font-size:0.9rem; color:#111827; }
        .zeebroo-mail .zb-status { margin-top:0.14rem; color:var(--zb-subtle); font-size:0.78rem; }
        .zeebroo-mail .zb-read-top { border-bottom:1px solid var(--zb-border); padding:0.72rem 0.95rem; display:flex; justify-content:space-between; align-items:center; gap:0.5rem; background:#fff; }
        .zeebroo-mail .zb-read-scroll { padding:0.95rem; overflow:auto; flex:1; min-height:0; }
        .zeebroo-mail .zb-empty { color:var(--zb-subtle); font-size:0.9rem; margin:0.6rem 0; }
        .zeebroo-mail .zb-message-card { border:1px solid var(--zb-border); border-radius:12px; padding:0.9rem; background:#fff; color:var(--zb-text); }
        .zeebroo-mail .zb-message-card h3 { margin:0 0 0.72rem; font-size:1rem; }
        .zeebroo-mail .zb-meta-grid { display:grid; gap:0.26rem; margin-bottom:0.75rem; color:var(--zb-subtle); font-size:0.84rem; }
        .zeebroo-mail .zb-body { border:1px solid var(--zb-border); border-radius:10px; padding:0.8rem; background:#fafbff; max-height:280px; overflow:auto; }
        .zeebroo-mail .zb-compose { margin-top:0.88rem; border:1px solid var(--zb-border); border-radius:12px; padding:0.78rem; background:#fff; }
        .zeebroo-mail .zb-compose h4 { margin:0 0 0.65rem; font-size:0.94rem; }
        .zeebroo-mail .zb-compose-grid { display:grid; gap:0.55rem; }
        .zeebroo-mail label { display:grid; gap:0.25rem; }
        .zeebroo-mail label > span { color:var(--zb-subtle); font-size:0.76rem; font-weight:600; }
        .zeebroo-mail select, .zeebroo-mail input, .zeebroo-mail textarea { width:100%; border:1px solid var(--zb-border); border-radius:8px; background:#fff; color:var(--zb-text); padding:0.48rem 0.56rem; font-size:0.88rem; }
        .zeebroo-mail textarea { min-height:110px; resize:vertical; }
        .zeebroo-mail select:focus, .zeebroo-mail input:focus, .zeebroo-mail textarea:focus { outline:none; border-color:var(--zb-primary); box-shadow:0 0 0 3px rgba(26, 115, 232, 0.14); }
        .zeebroo-mail .zb-compose-grid .btn-primary { width:100%; margin-top:0.2rem; }
        .zeebroo-mail .zb-error { color:#b91c1c; font-size:0.82rem; margin-top:0.55rem; }
        @media (max-width: 1400px) { .zeebroo-mail .zb-shell { grid-template-columns:210px 320px 1fr; } }
        @media (max-width: 1100px) { .zeebroo-mail .zb-shell { grid-template-columns:1fr; } .zeebroo-mail .zb-side, .zeebroo-mail .zb-list { border-right:none; border-bottom:1px solid var(--zb-border); } }
    </style>
    <header class="topbar">
        <div>
            <p class="eyebrow">Email</p>
            <h1>ZeeBroo Mail</h1>
            <p class="subtle">Host mailbox client for reading and sending emails.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
            @if ($emailModuleEnabled && \Illuminate\Support\Facades\Route::has('hosts.email.index'))
                <a class="btn-secondary" href="{{ route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'accounts']) }}">Manage Mailboxes</a>
            @endif
        </div>
    </header>

    @if (session('success'))
        <div class="server-card" style="border-left:4px solid #16a34a; margin-bottom:1rem;">
            <p class="subtle" style="margin:0;">{{ session('success') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="server-card" style="border-left:4px solid #dc2626; margin-bottom:1rem;">
            <p class="subtle" style="margin:0;">{{ $errors->first() }}</p>
        </div>
    @endif

    @if (! $emailModuleEnabled)
        <section class="server-card">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">Module unavailable</h2>
            <p class="subtle">Email module is currently disabled. Enable it first to use ZeeBroo Mail.</p>
        </section>
    @elseif ($accounts->isEmpty())
        <section class="server-card">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">No mailboxes found</h2>
            <p class="subtle">Create at least one mailbox in Email Manager before opening ZeeBroo Mail.</p>
        </section>
    @else
        @php $selectedAccount = $accounts->firstWhere('id', $selectedAccountId); @endphp
        <section class="server-card" style="padding:0.75rem;">
            <div class="zb-frame">
                <div class="zb-shell">
                    <aside class="zb-side">
                        <div class="zb-user">
                            <span>{{ $selectedAccount ? $selectedAccount->local_part.'@'.$selectedAccount->domain : 'Mailbox' }}</span>
                            <span>⋮</span>
                        </div>
                        <form method="get" action="{{ route('hosts.zeebroo-mail.index', $hosting) }}">
                            <label>
                                <span>Account</span>
                                <select name="account_id" required onchange="this.form.submit()">
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}" @selected((int) $selectedAccountId === (int) $account->id)>
                                            {{ $account->local_part }}@{{ $account->domain }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <noscript><button type="submit" class="btn-primary" style="margin-top:0.45rem;">Load mailbox</button></noscript>
                        </form>

                        <p class="zb-section-title">Folders</p>
                        <div class="zb-folder-list">
                            @foreach (($foldersResult['folders'] ?? ['INBOX']) as $folderName)
                                <a href="{{ route('hosts.zeebroo-mail.index', ['hosting' => $hosting, 'account_id' => $selectedAccountId, 'folder' => $folderName]) }}" class="zb-folder-item {{ $folder === $folderName ? 'active' : '' }}">
                                    <span>{{ $folderName }}</span><span>{{ $folder === $folderName ? '•' : '' }}</span>
                                </a>
                            @endforeach
                        </div>
                        @if (! ($foldersResult['ok'] ?? false) && ! empty($foldersResult['error']))
                            <p class="zb-error">{{ $foldersResult['error'] }}</p>
                        @endif
                    </aside>

                    <main class="zb-list">
                        <div class="zb-list-top">
                            <div class="zb-search">Search...</div>
                            <div class="zb-actions">
                                <span class="zb-chip">Folder: {{ $folder }}</span>
                                <span class="zb-chip">Messages: {{ is_array($mailboxResult['messages'] ?? null) ? count($mailboxResult['messages']) : 0 }}</span>
                                <span class="zb-chip">{{ now()->format('d M') }}</span>
                            </div>
                        </div>
                        <div class="zb-message-list">
                            @if (! $mailboxResult['ok'])
                                <p class="zb-empty" style="padding:0.75rem;">{{ $mailboxResult['error'] ?: 'Choose a mailbox to load messages.' }}</p>
                            @elseif (empty($mailboxResult['messages']))
                                <p class="zb-empty" style="padding:0.75rem;">No messages found in this folder.</p>
                            @else
                                @foreach ($mailboxResult['messages'] as $message)
                                    @php $isOpened = (int) (($messageResult['message']['uid'] ?? 0)) === (int) ($message['uid'] ?? 0); @endphp
                                    @if (($message['uid'] ?? 0) > 0)
                                        <a href="{{ route('hosts.zeebroo-mail.show', ['hosting' => $hosting, 'uid' => $message['uid'], 'account_id' => $selectedAccountId, 'folder' => $folder]) }}" class="zb-message-item {{ $isOpened ? 'active' : '' }}">
                                            <div class="zb-message-line">
                                                <span class="zb-from">{{ $message['from'] }}</span>
                                                <span class="zb-date">{{ $message['date'] }}</span>
                                            </div>
                                            <div class="zb-subject">{{ $message['subject'] }}</div>
                                            <div class="zb-status">{{ $message['seen'] ? 'Read' : 'Unread' }}</div>
                                        </a>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </main>

                    <aside class="zb-read">
                        <div class="zb-read-top"><strong>Message</strong></div>
                        <div class="zb-read-scroll">
                            @if (! $messageResult || ! ($messageResult['ok'] ?? false))
                                <p class="zb-empty">{{ $messageResult['error'] ?? 'Select a message from inbox to read.' }}</p>
                            @else
                                @php $opened = $messageResult['message']; @endphp
                                <article class="zb-message-card">
                                    <h3>{{ $opened['subject'] }}</h3>
                                    <div class="zb-meta-grid">
                                        <span><strong>From:</strong> {{ $opened['from'] }}</span>
                                        <span><strong>To:</strong> {{ $opened['to'] }}</span>
                                        <span><strong>Date:</strong> {{ $opened['date'] }}</span>
                                    </div>
                                    <div class="zb-body"><pre style="margin:0;white-space:pre-wrap;font-family:inherit;">{{ $opened['body'] }}</pre></div>
                                </article>
                            @endif

                            <div class="zb-compose">
                                <h4>Compose</h4>
                                <form method="post" action="{{ route('hosts.zeebroo-mail.send', $hosting) }}" class="zb-compose-grid">
                                    @csrf
                                    <input type="hidden" name="_context" value="send_email">
                                    <label>
                                        <span>From</span>
                                        <select name="from_account_id" required>
                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}" @selected((int) old('from_account_id', $selectedAccountId) === (int) $account->id)>
                                                    {{ $account->local_part }}@{{ $account->domain }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label><span>To</span><input type="email" name="to" value="{{ old('to') }}" required></label>
                                    <label><span>Subject</span><input type="text" name="subject" value="{{ old('subject') }}" required></label>
                                    <label><span>Message</span><textarea name="body" rows="6" required>{{ old('body') }}</textarea></label>
                                    <button type="submit" class="btn-primary">Send Email</button>
                                </form>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection

@section('right_sidebar')
@endsection
