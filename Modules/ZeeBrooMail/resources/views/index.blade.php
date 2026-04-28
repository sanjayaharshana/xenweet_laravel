@extends('layouts.host')

@section('title', 'ZeeBroo Mail - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope zeebroo-mail">
    <style>
        .zeebroo-mail { --zb-bg:#f6f8fc; --zb-surface:#fff; --zb-border:#dde3ea; --zb-text:#202124; --zb-subtle:#5f6368; --zb-primary:#1a73e8; --zb-primary-soft:#e8f0fe; overflow:hidden; }
        .zeebroo-mail .zb-main-card { position:fixed; top:66px; left:92px; right:16px; bottom:14px; height:auto; min-height:620px; max-height:none; margin:0; z-index:20; }
        .zeebroo-mail .zb-frame { background:var(--zb-bg); border:1px solid var(--zb-border); border-radius:14px; overflow:hidden; height:100%; min-height:0; }
        .zeebroo-mail .zb-shell { display:grid; grid-template-columns:240px 1fr; height:100%; min-height:0; }
        .zeebroo-mail .zb-side, .zeebroo-mail .zb-list { background:var(--zb-surface); }
        .zeebroo-mail .zb-side { border-right:1px solid var(--zb-border); padding:0.85rem 0.7rem; overflow:hidden; min-height:0; position:sticky; top:0; align-self:start; height:100%; }
        .zeebroo-mail .zb-list { border-right:1px solid var(--zb-border); display:flex; flex-direction:column; min-width:0; min-height:0; }
        .zeebroo-mail .zb-user { display:flex; align-items:center; justify-content:space-between; gap:0.4rem; border-radius:10px; padding:0.52rem 0.55rem; background:#f1f3f4; margin-bottom:0.8rem; color:var(--zb-text); font-size:0.84rem; font-weight:600; }
        .zeebroo-mail-top-account { display:flex; align-items:center; gap:0.4rem; margin-right:0.6rem; }
        .zeebroo-mail-top-account select { min-width:220px; max-width:320px; border:1px solid rgba(255,255,255,0.28); border-radius:8px; background:rgba(255,255,255,0.12); color:#fff; padding:0.35rem 0.5rem; font-size:0.82rem; }
        .zeebroo-mail-top-account select option { color:#111827; }
        .zeebroo-mail .zb-section-title { margin:0.85rem 0 0.42rem; color:var(--zb-subtle); font-size:0.75rem; text-transform:uppercase; letter-spacing:0.06em; font-weight:700; }
        .zeebroo-mail .zb-folder-list { display:flex; flex-direction:column; gap:0.22rem; }
        .zeebroo-mail .zb-folder-item { display:flex; align-items:center; justify-content:space-between; gap:0.5rem; border-radius:999px; padding:0.48rem 0.74rem; color:var(--zb-text); text-decoration:none; font-size:0.9rem; border:1px solid transparent; }
        .zeebroo-mail .zb-folder-main { display:inline-flex; align-items:center; gap:0.5rem; }
        .zeebroo-mail .zb-folder-main i { width:1rem; text-align:center; color:var(--zb-subtle); }
        .zeebroo-mail .zb-folder-item.active .zb-folder-main i { color:var(--zb-primary); }
        .zeebroo-mail .zb-folder-item:hover { background:#f1f3f4; }
        .zeebroo-mail .zb-folder-item.active { background:var(--zb-primary-soft); color:var(--zb-primary); font-weight:700; border-color:#d3e3fd; }
        .zeebroo-mail .zb-list-top { padding:0.72rem; border-bottom:1px solid var(--zb-border); display:grid; gap:0.55rem; position:sticky; top:0; background:var(--zb-surface); z-index:2; }
        .zeebroo-mail .zb-search-row { display:flex; align-items:center; gap:0.55rem; }
        .zeebroo-mail .zb-search-row .zb-search { flex:1; }
        .zeebroo-mail .zb-compose-btn { border:1px solid #bfdbfe; background:linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%); color:#1e3a8a; border-radius:999px; padding:0.5rem 0.9rem; font-size:0.84rem; font-weight:700; cursor:pointer; white-space:nowrap; display:inline-flex; align-items:center; gap:0.42rem; box-shadow:0 1px 2px rgba(30, 64, 175, 0.18); transition:all 0.16s ease; }
        .zeebroo-mail .zb-compose-btn:hover { background:linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%); box-shadow:0 6px 16px rgba(37, 99, 235, 0.18); transform:translateY(-1px); }
        .zeebroo-mail .zb-search { border:1px solid var(--zb-border); background:#f1f3f4; border-radius:999px; padding:0.52rem 0.78rem; color:var(--zb-subtle); font-size:0.9rem; }
        .zeebroo-mail .zb-actions { display:flex; gap:0.4rem; flex-wrap:wrap; }
        .zeebroo-mail .zb-chip { border:1px solid var(--zb-border); background:#fff; color:var(--zb-subtle); font-size:0.78rem; padding:0.2rem 0.48rem; border-radius:999px; }
        .zeebroo-mail .zb-dev-banner { margin-bottom:1rem; border:1px solid #c7d2fe; border-left:4px solid #4f46e5; border-radius:12px; background:linear-gradient(180deg, #eef2ff, #e0e7ff); padding:0.75rem 0.9rem; display:flex; gap:0.7rem; align-items:flex-start; }
        .zeebroo-mail .zb-dev-banner .icon { width:1.45rem; height:1.45rem; border-radius:999px; background:#4f46e5; color:#fff; font-weight:700; display:flex; align-items:center; justify-content:center; font-size:0.8rem; flex:0 0 auto; margin-top:0.1rem; }
        .zeebroo-mail .zb-dev-banner .title { margin:0 0 0.15rem; font-size:0.88rem; color:#312e81; font-weight:700; }
        .zeebroo-mail .zb-dev-banner .text { margin:0; color:#4338ca; font-size:0.84rem; }
        .zeebroo-mail .zb-message-list { overflow:auto; flex:1; min-height:0; }
        body.host-left-quicklinks-fixed .host-left-quicklinks { position:fixed; top:var(--main-navbar-height); left:0; width:78px; z-index:6; box-sizing:border-box; }
        .zeebroo-mail .zb-message-item { display:block; text-decoration:none; color:var(--zb-text); border-bottom:1px solid var(--zb-border); padding:0.62rem 0.72rem; background:#fff; }
        .zeebroo-mail .zb-message-item:hover { background:#f8fafd; }
        .zeebroo-mail .zb-message-item.active { background:var(--zb-primary-soft); }
        .zeebroo-mail .zb-message-line { display:flex; align-items:center; justify-content:space-between; gap:0.5rem; font-size:0.82rem; }
        .zeebroo-mail .zb-from { font-weight:700; color:#1f2937; }
        .zeebroo-mail .zb-date { color:var(--zb-subtle); white-space:nowrap; }
        .zeebroo-mail .zb-subject { margin-top:0.18rem; font-size:0.9rem; color:#111827; }
        .zeebroo-mail .zb-status { margin-top:0.14rem; color:var(--zb-subtle); font-size:0.78rem; }
        .zeebroo-mail .zb-empty { color:var(--zb-subtle); font-size:0.9rem; margin:0.6rem 0; }
        .zeebroo-mail .zb-message-card { border:1px solid var(--zb-border); border-radius:12px; padding:0.9rem; background:#fff; color:var(--zb-text); }
        .zeebroo-mail .zb-message-card h3 { margin:0 0 0.72rem; font-size:1rem; }
        .zeebroo-mail .zb-meta-grid { display:grid; gap:0.26rem; margin-bottom:0.75rem; color:var(--zb-subtle); font-size:0.84rem; }
        .zeebroo-mail .zb-body { border:1px solid var(--zb-border); border-radius:10px; padding:0.8rem; background:#fafbff; max-height:280px; overflow:auto; }
        .zeebroo-mail label { display:grid; gap:0.25rem; }
        .zeebroo-mail label > span { color:var(--zb-subtle); font-size:0.76rem; font-weight:600; }
        .zeebroo-mail select, .zeebroo-mail input, .zeebroo-mail textarea { width:100%; border:1px solid var(--zb-border); border-radius:8px; background:#fff; color:var(--zb-text); padding:0.48rem 0.56rem; font-size:0.88rem; }
        .zeebroo-mail textarea { min-height:110px; resize:vertical; }
        .zeebroo-mail select:focus, .zeebroo-mail input:focus, .zeebroo-mail textarea:focus { outline:none; border-color:var(--zb-primary); box-shadow:0 0 0 3px rgba(26, 115, 232, 0.14); }
        .zeebroo-mail .zb-error { color:#b91c1c; font-size:0.82rem; margin-top:0.55rem; }
        .zeebroo-mail .zb-note { color:var(--zb-subtle); font-size:0.82rem; margin-top:0.55rem; }
        .zb-modal-backdrop { position:fixed; inset:0; background:rgba(15, 23, 42, 0.55); display:none; align-items:center; justify-content:center; z-index:99999; padding:2rem 1rem; }
        .zb-modal-backdrop.open { display:flex; }
        .zb-modal { width:70vw; max-width:1100px; height:70vh; max-height:70vh; overflow:hidden; background:#fff; border-radius:14px; border:1px solid var(--zb-border); box-shadow:0 20px 40px rgba(0,0,0,0.2); display:flex; flex-direction:column; }
        .zb-modal-head { padding:0.8rem 1rem; border-bottom:1px solid var(--zb-border); display:flex; justify-content:space-between; align-items:center; flex:0 0 auto; }
        .zb-modal-body { padding:1rem; overflow:auto; flex:1 1 auto; }
        .zb-modal-close { border:1px solid var(--zb-border); background:#fff; border-radius:8px; padding:0.2rem 0.55rem; cursor:pointer; }
        .zb-modal-backdrop.open .zb-body { max-height:none; }
        .zb-compose-modal .zb-modal { width:min(760px, 92vw); height:auto; max-height:88vh; border-radius:16px; }
        .zb-compose-modal .zb-modal-head { background:linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%); }
        .zb-compose-modal .zb-modal-body { background:#fff; }
        .zb-compose-form { display:grid; gap:0.88rem; background:#f8fbff; border:1px solid #dbe5f1; border-radius:12px; padding:1rem; }
        .zb-compose-from { margin:0; padding:0.55rem 0.65rem; border:1px dashed #cbd5e1; border-radius:9px; background:#fff; color:#334155; font-size:0.84rem; }
        .zb-compose-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; }
        .zb-compose-form label { display:grid; gap:0.32rem; }
        .zb-compose-form label > span { margin:0; font-weight:700; font-size:0.75rem; letter-spacing:0.02em; color:#475569; }
        .zb-compose-form input,
        .zb-compose-form textarea { width:100%; background:#fff; border:1px solid #cfd8e3; border-radius:10px; padding:0.58rem 0.62rem; font-size:0.88rem; }
        .zb-compose-form input:focus,
        .zb-compose-form textarea:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.16); }
        .zb-compose-form textarea { min-height:260px; resize:vertical; }
        .zb-compose-actions { display:flex; justify-content:flex-end; gap:0.5rem; }
        .zb-send-btn { border:1px solid #1d4ed8; background:linear-gradient(180deg, #3b82f6 0%, #2563eb 100%); color:#fff; border-radius:10px; padding:0.5rem 1rem; font-size:0.84rem; font-weight:700; cursor:pointer; box-shadow:0 6px 16px rgba(37, 99, 235, 0.22); transition:all 0.15s ease; }
        .zb-send-btn:hover { background:linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%); transform:translateY(-1px); }
        .zb-send-btn:disabled { opacity:0.72; cursor:not-allowed; transform:none; box-shadow:none; }
        @media (max-width: 760px) {
            .zb-compose-grid { grid-template-columns:1fr; }
            .zb-compose-form textarea { min-height:190px; }
        }
        @media (max-width: 900px) { .zb-modal { width:94vw; height:82vh; max-height:82vh; } }
        @media (max-width: 1100px) { .zeebroo-mail { overflow:visible; } .zeebroo-mail .zb-main-card { position:relative; top:auto; left:auto; right:auto; bottom:auto; height:auto; min-height:0; max-height:none; } .zeebroo-mail .zb-frame { height:auto; max-height:none; } .zeebroo-mail .zb-shell { grid-template-columns:1fr; height:auto; } .zeebroo-mail .zb-side, .zeebroo-mail .zb-list { border-right:none; border-bottom:1px solid var(--zb-border); } }
    </style>
    @php
        $devImapDisabled = str_contains(mb_strtolower((string) ($mailboxResult['error'] ?? '')), 'disabled by environment');
    @endphp

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
        <section class="server-card zb-main-card" style="padding:0.75rem;">
            <div class="zb-frame">
                <div style="padding:0.75rem 0.75rem 0;">
                    @if (session('success'))
                        <div class="server-card" id="zb-flash-success" style="border-left:4px solid #16a34a; margin-bottom:0.75rem;">
                            <p class="subtle" id="zb-flash-success-text" style="margin:0;">{{ session('success') }}</p>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="server-card" id="zb-flash-error" style="border-left:4px solid #dc2626; margin-bottom:0.75rem;">
                            <p class="subtle" id="zb-flash-error-text" style="margin:0;">{{ $errors->first() }}</p>
                        </div>
                    @endif
                    @if ($devImapDisabled)
                        <div class="server-card" style="border-left:4px solid #4f46e5; margin-bottom:0.75rem;">
                            <p class="subtle" style="margin:0;">Development mode active: mailbox preview is disabled.</p>
                        </div>
                    @endif
                </div>
                <div class="zb-shell">
                    <aside class="zb-side">
                        <div class="zb-user">
                            <span>{{ $selectedAccount ? $selectedAccount->local_part.'@'.$selectedAccount->domain : 'Mailbox' }}</span>
                            <span>⋮</span>
                        </div>

                        @php
                            $defaultFolders = ['INBOX', 'Send', 'Draft', 'Spam', 'Filters', 'Settings'];
                            $uiFolders = collect(array_merge($defaultFolders, (array) ($foldersResult['folders'] ?? [])))
                                ->filter(fn ($name) => is_string($name) && trim($name) !== '')
                                ->unique()
                                ->values()
                                ->all();
                        @endphp
                        <div class="zb-folder-list" id="zb-folder-list">
                            @foreach ($uiFolders as $folderName)
                                @php
                                    $normalizedFolder = mb_strtolower(trim((string) $folderName));
                                    $folderIcon = match ($normalizedFolder) {
                                        'inbox' => 'fa-inbox',
                                        'send', 'sent', 'sent items' => 'fa-paper-plane',
                                        'draft', 'drafts' => 'fa-file-alt',
                                        'spam', 'junk' => 'fa-ban',
                                        'trash', 'bin', 'deleted', 'deleted items' => 'fa-trash',
                                        'archive' => 'fa-archive',
                                        'filters' => 'fa-filter',
                                        'settings' => 'fa-cog',
                                        default => 'fa-folder',
                                    };
                                @endphp
                                <a href="{{ route('hosts.zeebroo-mail.index', ['hosting' => $hosting, 'account_id' => $selectedAccountId, 'folder' => $folderName]) }}" data-folder="{{ $folderName }}" class="zb-folder-item {{ $folder === $folderName ? 'active' : '' }}">
                                    <span class="zb-folder-main"><i class="fa {{ $folderIcon }}" aria-hidden="true"></i><span>{{ $folderName }}</span></span><span>{{ $folder === $folderName ? '•' : '' }}</span>
                                </a>
                            @endforeach
                        </div>
                        @php
                            $folderErrorText = (string) ($foldersResult['error'] ?? '');
                            $folderErrorIsDevNote = str_contains(mb_strtolower($folderErrorText), 'disabled by environment');
                            if ($folderErrorIsDevNote) {
                                $folderErrorText = '';
                            }
                        @endphp
                        <p
                            class="{{ $folderErrorIsDevNote ? 'zb-note' : 'zb-error' }}"
                            id="zb-folder-error"
                            @if (($foldersResult['ok'] ?? false) || $folderErrorText === '') style="display:none;" @endif
                        >
                            {{ $folderErrorText }}
                        </p>
                    </aside>

                    <main class="zb-list">
                        <div class="zb-list-top">
                            <div class="zb-search-row">
                                <div class="zb-search">Search...</div>
                                <button type="button" class="zb-compose-btn" id="zb-compose-open"><i class="fa fa-pen" aria-hidden="true"></i> Compose</button>
                            </div>
                            <div class="zb-actions">
                                <span class="zb-chip" id="zb-folder-chip">Folder: {{ $folder }}</span>
                                <span class="zb-chip" id="zb-count-chip">Messages: {{ is_array($mailboxResult['messages'] ?? null) ? count($mailboxResult['messages']) : 0 }}</span>
                                <span class="zb-chip">{{ now()->format('d M') }}</span>
                            </div>
                        </div>
                        <div class="zb-message-list" id="zb-message-list">
                            @if (! $mailboxResult['ok'])
                                @php
                                    $mailboxErrorText = (string) ($mailboxResult['error'] ?? '');
                                    $mailboxErrorIsDevNote = str_contains(mb_strtolower($mailboxErrorText), 'disabled by environment');
                                @endphp
                                @if (! $mailboxErrorIsDevNote)
                                    <p class="zb-empty" style="padding:0.75rem;">{{ $mailboxErrorText ?: 'Choose a mailbox to load messages.' }}</p>
                                @endif
                            @elseif (empty($mailboxResult['messages']))
                                <p class="zb-empty" style="padding:0.75rem;">No messages found in this folder.</p>
                            @else
                                @foreach ($mailboxResult['messages'] as $message)
                                    @php $isOpened = (int) (($messageResult['message']['uid'] ?? 0)) === (int) ($message['uid'] ?? 0); @endphp
                                    @if (($message['uid'] ?? 0) > 0)
                                        <a href="{{ route('hosts.zeebroo-mail.show', ['hosting' => $hosting, 'uid' => $message['uid'], 'account_id' => $selectedAccountId, 'folder' => $folder]) }}" data-uid="{{ $message['uid'] }}" class="zb-message-item {{ $isOpened ? 'active' : '' }}">
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
                </div>
            </div>
        </section>
    @endif
</div>
<div class="zb-modal-backdrop" id="zb-message-modal">
    <div class="zb-modal">
        <div class="zb-modal-head">
            <strong>Message</strong>
            <button type="button" class="zb-modal-close" id="zb-modal-close">Close</button>
        </div>
        <div class="zb-modal-body" id="zb-modal-content">
            <p class="zb-empty">Select a message from inbox to read.</p>
        </div>
    </div>
</div>
<div class="zb-modal-backdrop zb-compose-modal" id="zb-compose-modal">
    <div class="zb-modal">
        <div class="zb-modal-head">
            <strong><i class="fa fa-pen-nib" aria-hidden="true" style="margin-right:0.45rem;"></i>Compose Mail</strong>
            <button type="button" class="zb-modal-close" id="zb-compose-close">Close</button>
        </div>
        <div class="zb-modal-body">
            <form class="zb-compose-form" id="zb-compose-form">
                <label>
                    <span>From</span>
                    <p class="zb-compose-from" id="zb-compose-from">{{ $selectedAccount ? $selectedAccount->local_part.'@'.$selectedAccount->domain : 'No sender account selected' }}</p>
                </label>
                <div class="zb-compose-grid">
                    <label>
                        <span>To</span>
                        <input type="text" name="to" id="zb-compose-to" placeholder="name@example.com, second@example.com" required>
                    </label>
                    <label>
                        <span>Subject</span>
                        <input type="text" name="subject" id="zb-compose-subject" placeholder="Subject">
                    </label>
                </div>
                <div class="zb-compose-grid">
                    <label>
                        <span>Cc</span>
                        <input type="text" name="cc" id="zb-compose-cc" placeholder="Optional">
                    </label>
                    <label>
                        <span>Bcc</span>
                        <input type="text" name="bcc" id="zb-compose-bcc" placeholder="Optional">
                    </label>
                </div>
                <label>
                    <span>Body</span>
                    <textarea name="body" id="zb-compose-body" placeholder="Write your message..." required></textarea>
                </label>
                <div class="zb-compose-actions">
                    <button type="submit" class="zb-send-btn" id="zb-compose-send">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function () {
        const state = {
            accountId: {{ (int) $selectedAccountId }},
            folder: @json($folder),
            hostingId: {{ (int) $hosting->id }},
        };
        const accountEmails = @json($accounts->mapWithKeys(fn ($account) => [(int) $account->id => (string) $account->local_part.'@'.(string) $account->domain])->all());
        const urls = {
            data: @json(route('hosts.zeebroo-mail.data', ['hosting' => $hosting])),
            messageBase: @json(url('/hosts/'.$hosting->id.'/zeebroo-mail/message')),
            sendAjax: @json(route('hosts.zeebroo-mail.send-ajax', ['hosting' => $hosting])),
        };

        const accountSelect = document.getElementById('zb-account-select');
        const folderList = document.getElementById('zb-folder-list');
        const messageList = document.getElementById('zb-message-list');
        const modal = document.getElementById('zb-message-modal');
        const modalContent = document.getElementById('zb-modal-content');
        const modalClose = document.getElementById('zb-modal-close');
        const composeModal = document.getElementById('zb-compose-modal');
        const composeOpen = document.getElementById('zb-compose-open');
        const composeClose = document.getElementById('zb-compose-close');
        const composeForm = document.getElementById('zb-compose-form');
        const composeFrom = document.getElementById('zb-compose-from');
        const composeTo = document.getElementById('zb-compose-to');
        const composeCc = document.getElementById('zb-compose-cc');
        const composeBcc = document.getElementById('zb-compose-bcc');
        const composeSubject = document.getElementById('zb-compose-subject');
        const composeBody = document.getElementById('zb-compose-body');
        const composeSend = document.getElementById('zb-compose-send');
        const folderChip = document.getElementById('zb-folder-chip');
        const countChip = document.getElementById('zb-count-chip');
        const folderError = document.getElementById('zb-folder-error');
        const flashSuccess = document.getElementById('zb-flash-success');
        const flashSuccessText = document.getElementById('zb-flash-success-text');
        const flashError = document.getElementById('zb-flash-error');
        const flashErrorText = document.getElementById('zb-flash-error-text');
        const csrfToken = @json(csrf_token());

        function setFlash(type, message) {
            if (type === 'success') {
                if (flashSuccess && flashSuccessText) {
                    flashSuccessText.textContent = message;
                    flashSuccess.style.display = 'block';
                }
                if (flashError) flashError.style.display = 'none';
            } else {
                if (flashError && flashErrorText) {
                    flashErrorText.textContent = message;
                    flashError.style.display = 'block';
                }
                if (flashSuccess) flashSuccess.style.display = 'none';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        async function fetchMailbox() {
            const params = new URLSearchParams({ account_id: String(state.accountId), folder: state.folder });
            const res = await fetch(urls.data + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            renderFolders(data.folders_result);
            renderMessages(data.mailbox_result);
            folderChip.textContent = 'Folder: ' + state.folder;
            countChip.textContent = 'Messages: ' + ((data.mailbox_result.messages || []).length);
        }

        function renderFolders(foldersResult) {
            const defaults = ['INBOX', 'Send', 'Draft', 'Spam', 'Filters', 'Settings'];
            const dynamic = (foldersResult && Array.isArray(foldersResult.folders)) ? foldersResult.folders : [];
            const folders = Array.from(new Set(defaults.concat(dynamic).filter((name) => typeof name === 'string' && name.trim() !== '')));
            const folderIconClass = (name) => {
                const normalized = String(name || '').trim().toLowerCase();
                if (normalized === 'inbox') return 'fa-inbox';
                if (['send', 'sent', 'sent items'].includes(normalized)) return 'fa-paper-plane';
                if (['draft', 'drafts'].includes(normalized)) return 'fa-file-alt';
                if (['spam', 'junk'].includes(normalized)) return 'fa-ban';
                if (['trash', 'bin', 'deleted', 'deleted items'].includes(normalized)) return 'fa-trash';
                if (normalized === 'archive') return 'fa-archive';
                if (normalized === 'filters') return 'fa-filter';
                if (normalized === 'settings') return 'fa-cog';
                return 'fa-folder';
            };
            folderList.innerHTML = folders.map((name) => {
                const active = name === state.folder ? ' active' : '';
                const dot = name === state.folder ? '•' : '';
                const iconClass = folderIconClass(name);
                return '<a href="#" data-folder="' + name + '" class="zb-folder-item' + active + '"><span class="zb-folder-main"><i class="fa ' + iconClass + '" aria-hidden="true"></i><span>' + escapeHtml(name) + '</span></span><span>' + dot + '</span></a>';
            }).join('');
            if (foldersResult && !foldersResult.ok && foldersResult.error) {
                const isDevNote = String(foldersResult.error).toLowerCase().includes('disabled by environment');
                if (isDevNote) {
                    folderError.style.display = 'none';
                    return;
                }
                folderError.classList.remove('zb-error', 'zb-note');
                folderError.classList.add(isDevNote ? 'zb-note' : 'zb-error');
                folderError.textContent = foldersResult.error;
                folderError.style.display = 'block';
            } else {
                folderError.style.display = 'none';
            }
        }

        function renderMessages(mailboxResult) {
            if (!mailboxResult || !mailboxResult.ok) {
                const msg = mailboxResult && mailboxResult.error ? mailboxResult.error : 'Choose a mailbox to load messages.';
                const isDevNote = String(msg).toLowerCase().includes('disabled by environment');
                messageList.innerHTML = isDevNote
                    ? ''
                    : '<p class="zb-empty" style="padding:0.75rem;">' + msg + '</p>';
                return;
            }
            if (!mailboxResult.messages || mailboxResult.messages.length === 0) {
                messageList.innerHTML = '<p class="zb-empty" style="padding:0.75rem;">No messages found in this folder.</p>';
                return;
            }
            messageList.innerHTML = mailboxResult.messages.map((m) => {
                if (!m.uid) return '';
                return '<a href="#" data-uid="' + m.uid + '" class="zb-message-item">'
                    + '<div class="zb-message-line"><span class="zb-from">' + escapeHtml(m.from) + '</span><span class="zb-date">' + escapeHtml(m.date) + '</span></div>'
                    + '<div class="zb-subject">' + escapeHtml(m.subject) + '</div>'
                    + '<div class="zb-status">' + (m.seen ? 'Read' : 'Unread') + '</div>'
                    + '</a>';
            }).join('');
        }

        async function openMessage(uid, anchor) {
            const params = new URLSearchParams({ account_id: String(state.accountId), folder: state.folder });
            const res = await fetch(urls.messageBase + '/' + uid + '/data?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (!data.ok) {
                renderModalError((data.message_result && data.message_result.error) ? data.message_result.error : 'Unable to open message.');
                return;
            }
            const opened = data.message_result.message;
            messageList.querySelectorAll('.zb-message-item.active').forEach((el) => el.classList.remove('active'));
            if (anchor) anchor.classList.add('active');
            modalContent.innerHTML = '<article class="zb-message-card">'
                + '<h3>' + escapeHtml(opened.subject) + '</h3>'
                + '<div class="zb-meta-grid">'
                + '<span><strong>From:</strong> ' + escapeHtml(opened.from) + '</span>'
                + '<span><strong>To:</strong> ' + escapeHtml(opened.to) + '</span>'
                + '<span><strong>Date:</strong> ' + escapeHtml(opened.date) + '</span>'
                + '</div>'
                + '<div class="zb-body"><pre style="margin:0;white-space:pre-wrap;font-family:inherit;">' + escapeHtml(opened.body) + '</pre></div>'
                + '</article>';
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function renderModalError(message) {
            modalContent.innerHTML = '<p class="zb-empty">' + escapeHtml(message) + '</p>';
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function openComposeModal() {
            if (!composeModal) return;
            if (composeFrom) {
                composeFrom.textContent = accountEmails[state.accountId] || 'No sender account selected';
            }
            composeModal.classList.add('open');
            document.body.style.overflow = 'hidden';
            if (composeTo) composeTo.focus();
        }

        function closeComposeModal() {
            if (!composeModal) return;
            composeModal.classList.remove('open');
            document.body.style.overflow = '';
        }

        async function sendComposeMail() {
            const to = composeTo ? composeTo.value.trim() : '';
            const cc = composeCc ? composeCc.value.trim() : '';
            const bcc = composeBcc ? composeBcc.value.trim() : '';
            const subject = composeSubject ? composeSubject.value.trim() : '';
            const body = composeBody ? composeBody.value.trim() : '';
            if (!to || !body) {
                setFlash('error', 'Recipient and body are required.');
                return;
            }

            if (composeSend) {
                composeSend.disabled = true;
                composeSend.textContent = 'Sending...';
            }

            try {
                const payload = new FormData();
                payload.append('_token', csrfToken);
                payload.append('from_account_id', String(state.accountId));
                payload.append('to', to);
                payload.append('cc', cc);
                payload.append('bcc', bcc);
                payload.append('subject', subject);
                payload.append('body', body);
                const res = await fetch(urls.sendAjax, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: payload,
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    throw new Error((data && data.error) ? data.error : 'Unable to send message.');
                }

                setFlash('success', data.message || 'Message sent successfully.');
                if (composeForm) composeForm.reset();
                closeComposeModal();
                await fetchMailbox();
            } catch (error) {
                setFlash('error', error.message || 'Failed to send message.');
            } finally {
                if (composeSend) {
                    composeSend.disabled = false;
                    composeSend.textContent = 'Send';
                }
            }
        }

        function escapeHtml(s) {
            return String(s || '').replace(/[&<>"']/g, function (c) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c];
            });
        }

        if (accountSelect) {
            accountSelect.addEventListener('change', function () {
                state.accountId = parseInt(this.value, 10) || 0;
                fetchMailbox().catch(() => setFlash('error', 'Failed to load mailbox.'));
            });
        }

        if (folderList) {
            folderList.addEventListener('click', function (e) {
                const link = e.target.closest('a[data-folder]');
                if (!link) return;
                e.preventDefault();
                state.folder = link.getAttribute('data-folder') || 'INBOX';
                fetchMailbox().catch(() => setFlash('error', 'Failed to load folder.'));
            });
        }

        if (messageList) {
            messageList.addEventListener('click', function (e) {
                const link = e.target.closest('a[data-uid]');
                if (!link) return;
                e.preventDefault();
                openMessage(link.getAttribute('data-uid'), link).catch(() => setFlash('error', 'Failed to open message.'));
            });
        }

        if (modalClose) {
            modalClose.addEventListener('click', function () {
                modal.classList.remove('open');
                document.body.style.overflow = '';
            });
        }
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    modal.classList.remove('open');
                    document.body.style.overflow = '';
                }
            });
        }
        if (composeOpen) {
            composeOpen.addEventListener('click', function () {
                openComposeModal();
            });
        }
        if (composeClose) {
            composeClose.addEventListener('click', function () {
                closeComposeModal();
            });
        }
        if (composeModal) {
            composeModal.addEventListener('click', function (e) {
                if (e.target === composeModal) {
                    closeComposeModal();
                }
            });
        }
        if (composeForm) {
            composeForm.addEventListener('submit', function (e) {
                e.preventDefault();
                sendComposeMail();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && modal.classList.contains('open')) {
                modal.classList.remove('open');
                document.body.style.overflow = '';
            }
            if (e.key === 'Escape' && composeModal && composeModal.classList.contains('open')) {
                closeComposeModal();
            }
        });

    })();
</script>
@endsection

@section('right_sidebar')
@endsection

@section('disable_right_sidebar')
@endsection

@section('disable_top_nav_items')
@endsection

@section('disable_top_nav_user')
@endsection

@section('fix_left_quicklinks')
@endsection

@section('top_nav_extra')
    <form class="zeebroo-mail-top-account" method="get" action="{{ route('hosts.zeebroo-mail.index', $hosting) }}">
        <select id="zb-account-select" name="account_id" required onchange="this.form.submit()">
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected((int) $selectedAccountId === (int) $account->id)>
                    {{ $account->local_part }}@{{ $account->domain }}
                </option>
            @endforeach
        </select>
    </form>
@endsection
