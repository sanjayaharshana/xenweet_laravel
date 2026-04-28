@extends('layouts.host')

@section('title', 'WebMail - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope managedb-scope webmail-client">
    <style>
        .webmail-client { --wm-border: rgba(148, 163, 184, 0.22); --wm-bg-soft: rgba(15, 23, 42, 0.2); --wm-bg-strong: rgba(15, 23, 42, 0.34); --wm-accent: #38bdf8; }
        .webmail-client .webmail-toolbar { display:flex; justify-content:space-between; align-items:center; gap:0.75rem; margin-bottom:1rem; padding:0.7rem 0.85rem; border:1px solid var(--wm-border); border-radius:12px; background:linear-gradient(180deg, rgba(30,41,59,0.42), rgba(15,23,42,0.3)); }
        .webmail-client .webmail-toolbar-meta { display:flex; gap:0.5rem; flex-wrap:wrap; }
        .webmail-client .webmail-pill { font-size:0.76rem; font-weight:600; letter-spacing:0.02em; padding:0.25rem 0.58rem; border-radius:999px; border:1px solid var(--wm-border); background:rgba(15,23,42,0.4); color:var(--text-soft, #cbd5e1); }
        .webmail-client .webmail-shell { display:grid; grid-template-columns:260px 1fr 380px; gap:1rem; align-items:start; }
        .webmail-client .webmail-col { min-height:300px; border:1px solid var(--wm-border); border-radius:14px; padding:0.9rem; background:var(--wm-bg-soft); box-shadow:0 8px 30px rgba(2, 6, 23, 0.12); }
        .webmail-client .webmail-pane-title { margin:0 0 0.75rem 0; font-size:1rem; letter-spacing:0.01em; font-weight:700; }
        .webmail-client .webmail-col main,
        .webmail-client .webmail-col aside { min-width:0; }
        .webmail-client .webmail-folder-list,
        .webmail-client .webmail-message-list { display:flex; flex-direction:column; gap:0.45rem; }
        .webmail-client .webmail-folder-item,
        .webmail-client .webmail-message-item { display:block; text-decoration:none; border:1px solid var(--wm-border); border-radius:10px; padding:0.68rem 0.78rem; background:var(--wm-bg-strong); transition:all 0.2s ease; }
        .webmail-client .webmail-folder-item:hover,
        .webmail-client .webmail-message-item:hover { border-color:rgba(56, 189, 248, 0.55); transform:translateY(-1px); }
        .webmail-client .webmail-folder-item.active,
        .webmail-client .webmail-message-item.active { border-color:rgba(56, 189, 248, 0.78); background:rgba(14, 165, 233, 0.16); box-shadow:inset 0 0 0 1px rgba(56, 189, 248, 0.4); }
        .webmail-client .webmail-folder-item .name { font-weight:600; display:block; }
        .webmail-client .webmail-message-item .subject { display:block; font-weight:600; margin-bottom:0.2rem; }
        .webmail-client .webmail-message-item .meta { display:flex; justify-content:space-between; gap:0.5rem; font-size:0.82rem; color:var(--text-soft, #94a3b8); }
        .webmail-client .webmail-read-view { display:flex; flex-direction:column; gap:0.75rem; }
        .webmail-client .webmail-body { border:1px solid var(--wm-border); border-radius:10px; padding:0.85rem; background:rgba(15,23,42,0.3); max-height:330px; overflow:auto; }
        .webmail-client .webmail-compose { margin-top:1rem; border-top:1px solid var(--wm-border); padding-top:1rem; }
        .webmail-client .webmail-compose-grid { display:grid; gap:0.65rem; }
        .webmail-client .webmail-muted { color:var(--text-soft, #94a3b8); font-size:0.88rem; margin:0; }
        .webmail-client label { display:grid; gap:0.32rem; }
        .webmail-client label > span { font-size:0.8rem; font-weight:600; color:var(--text-soft, #cbd5e1); letter-spacing:0.01em; }
        .webmail-client select,
        .webmail-client input,
        .webmail-client textarea { width:100%; border:1px solid var(--wm-border); border-radius:9px; background:rgba(15, 23, 42, 0.52); color:var(--text-main, #e2e8f0); padding:0.56rem 0.62rem; font-size:0.9rem; }
        .webmail-client textarea { min-height:120px; resize:vertical; }
        .webmail-client select:focus,
        .webmail-client input:focus,
        .webmail-client textarea:focus { outline:none; border-color:rgba(56, 189, 248, 0.8); box-shadow:0 0 0 3px rgba(56, 189, 248, 0.18); }
        .webmail-client .webmail-compose-grid .btn-primary { width:100%; margin-top:0.2rem; }
        @media (max-width: 1300px) { .webmail-client .webmail-shell { grid-template-columns:230px 1fr; } .webmail-client .webmail-col--reader { grid-column:1 / -1; } }
        @media (max-width: 900px) { .webmail-client .webmail-shell { grid-template-columns:1fr; } }
    </style>
    <header class="topbar">
        <div>
            <p class="eyebrow">Email</p>
            <h1>WebMail</h1>
            <p class="subtle">Inbox-style WebMail client with folders, message list, and quick composer.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
            @if ($emailModuleEnabled && \Illuminate\Support\Facades\Route::has('hosts.email.index'))
                <a class="btn-secondary" href="{{ route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'accounts']) }}">Manage Mailboxes</a>
            @endif
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

    @if (! $emailModuleEnabled)
        <section class="server-card">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">Module unavailable</h2>
            <p class="subtle">Email module is currently disabled. Enable it first to use WebMail mailbox accounts.</p>
        </section>
    @elseif ($accounts->isEmpty())
        <section class="server-card">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">No mailboxes found</h2>
            <p class="subtle">Create at least one mailbox in Email Manager before opening WebMail.</p>
        </section>
    @else
        <section class="server-card">
            @php
                $selectedAccount = $accounts->firstWhere('id', $selectedAccountId);
            @endphp
            <div class="webmail-toolbar">
                <div class="webmail-toolbar-meta">
                    <span class="webmail-pill">Account: {{ $selectedAccount ? $selectedAccount->local_part.'@'.$selectedAccount->domain : 'N/A' }}</span>
                    <span class="webmail-pill">Folder: {{ $folder }}</span>
                    <span class="webmail-pill">Messages: {{ is_array($mailboxResult['messages'] ?? null) ? count($mailboxResult['messages']) : 0 }}</span>
                </div>
                <span class="webmail-pill">{{ now()->format('d M Y') }}</span>
            </div>
            <div class="webmail-shell">
                <aside class="webmail-col">
                    <h2 class="webmail-pane-title">Mailbox</h2>
                    <form method="get" action="{{ route('hosts.webmail.index', $hosting) }}" style="display:grid;gap:0.6rem;margin-bottom:0.95rem;">
                        <label>
                            <span class="subtle">Account</span>
                            <select name="account_id" required onchange="this.form.submit()">
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected((int) $selectedAccountId === (int) $account->id)>
                                        {{ $account->local_part }}@{{ $account->domain }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <noscript><button type="submit" class="btn-primary">Load mailbox</button></noscript>
                    </form>

                    <h3 class="webmail-pane-title">Folders</h3>
                    <div class="webmail-folder-list">
                        @foreach (($foldersResult['folders'] ?? ['INBOX']) as $folderName)
                            <a
                                href="{{ route('hosts.webmail.index', ['hosting' => $hosting, 'account_id' => $selectedAccountId, 'folder' => $folderName]) }}"
                                class="webmail-folder-item {{ $folder === $folderName ? 'active' : '' }}"
                            >
                                <span class="name">{{ $folderName }}</span>
                                <span class="webmail-muted">{{ $folder === $folderName ? 'Current folder' : 'Open folder' }}</span>
                            </a>
                        @endforeach
                    </div>
                    @if (! ($foldersResult['ok'] ?? false) && ! empty($foldersResult['error']))
                        <p class="webmail-muted" style="margin-top:0.6rem;">{{ $foldersResult['error'] }}</p>
                    @endif
                </aside>

                <main class="webmail-col">
                    <h2 class="webmail-pane-title">Inbox ({{ $folder }})</h2>
                    @if (! $mailboxResult['ok'])
                        <p class="webmail-muted">{{ $mailboxResult['error'] ?: 'Choose a mailbox to load messages.' }}</p>
                    @elseif (empty($mailboxResult['messages']))
                        <p class="webmail-muted">No messages found in this folder.</p>
                    @else
                        <div class="webmail-message-list">
                            @foreach ($mailboxResult['messages'] as $message)
                                @php
                                    $isOpened = (int) (($messageResult['message']['uid'] ?? 0)) === (int) ($message['uid'] ?? 0);
                                @endphp
                                @if (($message['uid'] ?? 0) > 0)
                                    <a
                                        href="{{ route('hosts.webmail.show', ['hosting' => $hosting, 'uid' => $message['uid'], 'account_id' => $selectedAccountId, 'folder' => $folder]) }}"
                                        class="webmail-message-item {{ $isOpened ? 'active' : '' }}"
                                    >
                                        <span class="subject">{{ $message['subject'] }}</span>
                                        <div class="meta">
                                            <span>{{ $message['from'] }}</span>
                                            <span>{{ $message['seen'] ? 'Read' : 'Unread' }}</span>
                                        </div>
                                        <p class="webmail-muted" style="margin-top:0.35rem;">{{ $message['date'] }}</p>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </main>

                <aside class="webmail-col webmail-col--reader">
                    <h2 class="webmail-pane-title">Reader</h2>
                    <div class="webmail-read-view">
                        @if (! $messageResult || ! ($messageResult['ok'] ?? false))
                            <p class="webmail-muted">{{ $messageResult['error'] ?? 'Select a message from inbox to read.' }}</p>
                        @else
                            @php $opened = $messageResult['message']; @endphp
                            <p class="webmail-muted"><strong>Subject:</strong> {{ $opened['subject'] }}</p>
                            <p class="webmail-muted"><strong>From:</strong> {{ $opened['from'] }}</p>
                            <p class="webmail-muted"><strong>To:</strong> {{ $opened['to'] }}</p>
                            <p class="webmail-muted"><strong>Date:</strong> {{ $opened['date'] }}</p>
                            <div class="webmail-body">
                                <pre style="margin:0;white-space:pre-wrap;font-family:inherit;">{{ $opened['body'] }}</pre>
                            </div>
                        @endif
                    </div>

                    <div class="webmail-compose">
                        <h3 class="webmail-pane-title">Compose</h3>
                        <form method="post" action="{{ route('hosts.webmail.send', $hosting) }}" class="webmail-compose-grid">
                            @csrf
                            <input type="hidden" name="_context" value="send_email">
                            <label>
                                <span class="subtle">From</span>
                                <select name="from_account_id" required>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}" @selected((int) old('from_account_id', $selectedAccountId) === (int) $account->id)>
                                            {{ $account->local_part }}@{{ $account->domain }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span class="subtle">To</span>
                                <input type="email" name="to" value="{{ old('to') }}" required>
                            </label>
                            <label>
                                <span class="subtle">Subject</span>
                                <input type="text" name="subject" value="{{ old('subject') }}" required>
                            </label>
                            <label>
                                <span class="subtle">Message</span>
                                <textarea name="body" rows="6" required>{{ old('body') }}</textarea>
                            </label>
                            <button type="submit" class="btn-primary">Send Email</button>
                        </form>
                    </div>
                </aside>
            </div>
        </section>
    @endif
</div>
@endsection
