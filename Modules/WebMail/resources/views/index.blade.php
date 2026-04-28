@extends('layouts.host')

@section('title', 'WebMail - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope managedb-scope webmail-client">
    <style>
        .webmail-client {
            --wm-bg: #f6f8fc;
            --wm-surface: #ffffff;
            --wm-surface-muted: #f1f3f4;
            --wm-border: #dde3ea;
            --wm-text: #202124;
            --wm-subtle: #5f6368;
            --wm-primary: #1a73e8;
            --wm-primary-soft: #e8f0fe;
        }
        .webmail-client .webmail-frame {
            background: var(--wm-bg);
            border: 1px solid var(--wm-border);
            border-radius: 14px;
            overflow: hidden;
        }
        .webmail-client .webmail-shell {
            display: grid;
            grid-template-columns: 220px 360px 1fr;
            min-height: 680px;
        }
        .webmail-client .wm-side,
        .webmail-client .wm-list,
        .webmail-client .wm-read {
            background: var(--wm-surface);
        }
        .webmail-client .wm-side {
            border-right: 1px solid var(--wm-border);
            padding: 0.85rem 0.7rem;
        }
        .webmail-client .wm-list {
            border-right: 1px solid var(--wm-border);
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .webmail-client .wm-read {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .webmail-client .wm-user {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
            border-radius: 10px;
            padding: 0.52rem 0.55rem;
            background: var(--wm-surface-muted);
            margin-bottom: 0.8rem;
            color: var(--wm-text);
            font-size: 0.84rem;
            font-weight: 600;
        }
        .webmail-client .wm-section-title {
            margin: 0.85rem 0 0.42rem;
            color: var(--wm-subtle);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }
        .webmail-client .wm-folder-list {
            display: flex;
            flex-direction: column;
            gap: 0.22rem;
        }
        .webmail-client .wm-folder-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            border-radius: 999px;
            padding: 0.48rem 0.74rem;
            color: var(--wm-text);
            text-decoration: none;
            font-size: 0.9rem;
            border: 1px solid transparent;
        }
        .webmail-client .wm-folder-item:hover {
            background: var(--wm-surface-muted);
        }
        .webmail-client .wm-folder-item.active {
            background: var(--wm-primary-soft);
            color: var(--wm-primary);
            font-weight: 700;
            border-color: #d3e3fd;
        }
        .webmail-client .wm-folder-meta {
            color: var(--wm-subtle);
            font-size: 0.78rem;
        }
        .webmail-client .wm-list-top {
            padding: 0.72rem;
            border-bottom: 1px solid var(--wm-border);
            display: grid;
            gap: 0.55rem;
        }
        .webmail-client .wm-search {
            border: 1px solid var(--wm-border);
            background: var(--wm-surface-muted);
            border-radius: 999px;
            padding: 0.52rem 0.78rem;
            color: var(--wm-subtle);
            font-size: 0.9rem;
        }
        .webmail-client .wm-actions {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }
        .webmail-client .wm-chip {
            border: 1px solid var(--wm-border);
            background: var(--wm-surface);
            color: var(--wm-subtle);
            font-size: 0.78rem;
            padding: 0.2rem 0.48rem;
            border-radius: 999px;
        }
        .webmail-client .wm-message-list {
            overflow: auto;
            flex: 1;
        }
        .webmail-client .wm-message-item {
            display: block;
            text-decoration: none;
            color: var(--wm-text);
            border-bottom: 1px solid var(--wm-border);
            padding: 0.62rem 0.72rem;
            background: #fff;
        }
        .webmail-client .wm-message-item:hover {
            background: #f8fafd;
        }
        .webmail-client .wm-message-item.active {
            background: var(--wm-primary-soft);
        }
        .webmail-client .wm-message-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            font-size: 0.82rem;
        }
        .webmail-client .wm-from {
            font-weight: 700;
            color: #1f2937;
        }
        .webmail-client .wm-date {
            color: var(--wm-subtle);
            white-space: nowrap;
        }
        .webmail-client .wm-subject {
            margin-top: 0.18rem;
            font-size: 0.9rem;
            color: #111827;
        }
        .webmail-client .wm-status {
            margin-top: 0.14rem;
            color: var(--wm-subtle);
            font-size: 0.78rem;
        }
        .webmail-client .wm-read-top {
            border-bottom: 1px solid var(--wm-border);
            padding: 0.72rem 0.95rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
            background: #fff;
        }
        .webmail-client .wm-read-actions {
            display: flex;
            gap: 0.32rem;
            flex-wrap: wrap;
        }
        .webmail-client .wm-action {
            border: 1px solid var(--wm-border);
            background: var(--wm-surface);
            color: var(--wm-subtle);
            border-radius: 8px;
            padding: 0.28rem 0.52rem;
            font-size: 0.76rem;
            font-weight: 600;
        }
        .webmail-client .wm-read-scroll {
            padding: 0.95rem;
            overflow: auto;
            flex: 1;
            min-height: 0;
        }
        .webmail-client .wm-empty {
            color: var(--wm-subtle);
            font-size: 0.9rem;
            margin: 0.6rem 0;
        }
        .webmail-client .wm-message-card {
            border: 1px solid var(--wm-border);
            border-radius: 12px;
            padding: 0.9rem;
            background: #fff;
            color: var(--wm-text);
        }
        .webmail-client .wm-message-card h3 {
            margin: 0 0 0.72rem;
            font-size: 1rem;
        }
        .webmail-client .wm-meta-grid {
            display: grid;
            gap: 0.26rem;
            margin-bottom: 0.75rem;
            color: var(--wm-subtle);
            font-size: 0.84rem;
        }
        .webmail-client .wm-body {
            border: 1px solid var(--wm-border);
            border-radius: 10px;
            padding: 0.8rem;
            background: #fafbff;
            max-height: 280px;
            overflow: auto;
        }
        .webmail-client .wm-compose {
            margin-top: 0.88rem;
            border: 1px solid var(--wm-border);
            border-radius: 12px;
            padding: 0.78rem;
            background: #fff;
        }
        .webmail-client .wm-compose h4 {
            margin: 0 0 0.65rem;
            font-size: 0.94rem;
        }
        .webmail-client .wm-compose-grid {
            display: grid;
            gap: 0.55rem;
        }
        .webmail-client label { display: grid; gap: 0.25rem; }
        .webmail-client label > span {
            color: var(--wm-subtle);
            font-size: 0.76rem;
            font-weight: 600;
        }
        .webmail-client select,
        .webmail-client input,
        .webmail-client textarea {
            width: 100%;
            border: 1px solid var(--wm-border);
            border-radius: 8px;
            background: #fff;
            color: var(--wm-text);
            padding: 0.48rem 0.56rem;
            font-size: 0.88rem;
        }
        .webmail-client textarea { min-height: 110px; resize: vertical; }
        .webmail-client select:focus,
        .webmail-client input:focus,
        .webmail-client textarea:focus { outline:none; border-color:var(--wm-primary); box-shadow:0 0 0 3px rgba(26, 115, 232, 0.14); }
        .webmail-client .wm-compose-grid .btn-primary { width: 100%; margin-top: 0.2rem; }
        .webmail-client .wm-module-error { color: #b91c1c; font-size: 0.82rem; margin-top: 0.55rem; }
        @media (max-width: 1400px) { .webmail-client .webmail-shell { grid-template-columns: 210px 320px 1fr; } }
        @media (max-width: 1100px) { .webmail-client .webmail-shell { grid-template-columns: 1fr; } .webmail-client .wm-side, .webmail-client .wm-list { border-right: none; border-bottom: 1px solid var(--wm-border); } }
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
        <section class="server-card" style="padding:0.75rem;">
            @php
                $selectedAccount = $accounts->firstWhere('id', $selectedAccountId);
            @endphp
            <div class="webmail-frame">
                <div class="webmail-shell">
                    <aside class="wm-side">
                        <div class="wm-user">
                            <span>{{ $selectedAccount ? $selectedAccount->local_part.'@'.$selectedAccount->domain : 'Mailbox' }}</span>
                            <span title="Mailbox options">⋮</span>
                        </div>
                        <form method="get" action="{{ route('hosts.webmail.index', $hosting) }}">
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

                        <p class="wm-section-title">Folders</p>
                        <div class="wm-folder-list">
                            @foreach (($foldersResult['folders'] ?? ['INBOX']) as $folderName)
                                <a
                                    href="{{ route('hosts.webmail.index', ['hosting' => $hosting, 'account_id' => $selectedAccountId, 'folder' => $folderName]) }}"
                                    class="wm-folder-item {{ $folder === $folderName ? 'active' : '' }}"
                                >
                                    <span>{{ $folderName }}</span>
                                    <span class="wm-folder-meta">{{ $folder === $folderName ? '•' : '' }}</span>
                                </a>
                            @endforeach
                        </div>
                        @if (! ($foldersResult['ok'] ?? false) && ! empty($foldersResult['error']))
                            <p class="wm-module-error">{{ $foldersResult['error'] }}</p>
                        @endif
                    </aside>

                    <main class="wm-list">
                        <div class="wm-list-top">
                            <div class="wm-search">Search...</div>
                            <div class="wm-actions">
                                <span class="wm-chip">Folder: {{ $folder }}</span>
                                <span class="wm-chip">Messages: {{ is_array($mailboxResult['messages'] ?? null) ? count($mailboxResult['messages']) : 0 }}</span>
                                <span class="wm-chip">{{ now()->format('d M') }}</span>
                            </div>
                        </div>
                        <div class="wm-message-list">
                            @if (! $mailboxResult['ok'])
                                <p class="wm-empty" style="padding:0.75rem;">{{ $mailboxResult['error'] ?: 'Choose a mailbox to load messages.' }}</p>
                            @elseif (empty($mailboxResult['messages']))
                                <p class="wm-empty" style="padding:0.75rem;">No messages found in this folder.</p>
                            @else
                                @foreach ($mailboxResult['messages'] as $message)
                                    @php
                                        $isOpened = (int) (($messageResult['message']['uid'] ?? 0)) === (int) ($message['uid'] ?? 0);
                                    @endphp
                                    @if (($message['uid'] ?? 0) > 0)
                                        <a
                                            href="{{ route('hosts.webmail.show', ['hosting' => $hosting, 'uid' => $message['uid'], 'account_id' => $selectedAccountId, 'folder' => $folder]) }}"
                                            class="wm-message-item {{ $isOpened ? 'active' : '' }}"
                                        >
                                            <div class="wm-message-line">
                                                <span class="wm-from">{{ $message['from'] }}</span>
                                                <span class="wm-date">{{ $message['date'] }}</span>
                                            </div>
                                            <div class="wm-subject">{{ $message['subject'] }}</div>
                                            <div class="wm-status">{{ $message['seen'] ? 'Read' : 'Unread' }}</div>
                                        </a>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </main>

                    <aside class="wm-read">
                        <div class="wm-read-top">
                            <strong>Message</strong>
                            <div class="wm-read-actions">
                                <span class="wm-action">Reply</span>
                                <span class="wm-action">Forward</span>
                                <span class="wm-action">Delete</span>
                            </div>
                        </div>
                        <div class="wm-read-scroll">
                            @if (! $messageResult || ! ($messageResult['ok'] ?? false))
                                <p class="wm-empty">{{ $messageResult['error'] ?? 'Select a message from inbox to read.' }}</p>
                            @else
                                @php $opened = $messageResult['message']; @endphp
                                <article class="wm-message-card">
                                    <h3>{{ $opened['subject'] }}</h3>
                                    <div class="wm-meta-grid">
                                        <span><strong>From:</strong> {{ $opened['from'] }}</span>
                                        <span><strong>To:</strong> {{ $opened['to'] }}</span>
                                        <span><strong>Date:</strong> {{ $opened['date'] }}</span>
                                    </div>
                                    <div class="wm-body">
                                        <pre style="margin:0;white-space:pre-wrap;font-family:inherit;">{{ $opened['body'] }}</pre>
                                    </div>
                                </article>
                            @endif

                            <div class="wm-compose">
                                <h4>Compose</h4>
                                <form method="post" action="{{ route('hosts.webmail.send', $hosting) }}" class="wm-compose-grid">
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
                            <label>
                                <span>To</span>
                                <input type="email" name="to" value="{{ old('to') }}" required>
                            </label>
                            <label>
                                <span>Subject</span>
                                <input type="text" name="subject" value="{{ old('subject') }}" required>
                            </label>
                            <label>
                                <span>Message</span>
                                <textarea name="body" rows="6" required>{{ old('body') }}</textarea>
                            </label>
                            <button type="submit" class="btn-primary">Send Email</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
