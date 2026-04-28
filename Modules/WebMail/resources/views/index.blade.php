@extends('layouts.host')

@section('title', 'WebMail - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope managedb-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Email</p>
            <h1>WebMail</h1>
            <p class="subtle">Read inbox messages and send emails directly from the host panel.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
            <a class="btn-secondary" href="{{ route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'accounts']) }}">Manage Mailboxes</a>
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
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">Open mailbox</h2>
            <form method="get" action="{{ route('hosts.webmail.index', $hosting) }}">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0.75rem;">
                    <label>
                        <span class="subtle">Mailbox</span>
                        <select name="account_id" required>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((int) $selectedAccountId === (int) $account->id)>
                                    {{ $account->local_part }}@{{ $account->domain }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="subtle">Folder</span>
                        <select name="folder">
                            @foreach (($foldersResult['folders'] ?? ['INBOX']) as $folderName)
                                <option value="{{ $folderName }}" @selected($folder === $folderName)>{{ $folderName }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div style="margin-top:0.8rem;">
                    <button type="submit" class="btn-primary">Load Messages</button>
                </div>
                @if (! ($foldersResult['ok'] ?? false) && ! empty($foldersResult['error']))
                    <p class="subtle" style="margin-top:0.6rem;">{{ $foldersResult['error'] }}</p>
                @endif
            </form>
        </section>

        <section class="server-card" style="margin-top:1rem;">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">Compose message</h2>
            <form method="post" action="{{ route('hosts.webmail.send', $hosting) }}" class="managedb-form">
                @csrf
                <input type="hidden" name="_context" value="send_email">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0.75rem;">
                    <label>
                        <span class="subtle">From mailbox</span>
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
                    <label style="grid-column:1/-1;">
                        <span class="subtle">Message</span>
                        <textarea name="body" rows="6" required>{{ old('body') }}</textarea>
                    </label>
                </div>
                <div style="margin-top:0.8rem;">
                    <button type="submit" class="btn-primary">Send Email</button>
                </div>
            </form>
        </section>

        <section class="server-card" style="margin-top:1rem;">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">Mailbox preview ({{ $folder }})</h2>
            @if (! $mailboxResult['ok'])
                <p class="subtle">{{ $mailboxResult['error'] ?: 'Choose a mailbox and click Load Messages.' }}</p>
            @elseif (empty($mailboxResult['messages']))
                <p class="subtle">No messages found in this folder.</p>
            @else
                <div class="file-table" style="margin-top:0.65rem;">
                    <div class="file-row file-row-head">
                        <span>Subject</span>
                        <span>From</span>
                        <span>Date</span>
                        <span>Status</span>
                    </div>
                    @foreach ($mailboxResult['messages'] as $message)
                        <div class="file-row">
                            <span>
                                @if (($message['uid'] ?? 0) > 0)
                                    <a href="{{ route('hosts.webmail.show', ['hosting' => $hosting, 'uid' => $message['uid'], 'account_id' => $selectedAccountId, 'folder' => $folder]) }}">
                                        {{ $message['subject'] }}
                                    </a>
                                @else
                                    {{ $message['subject'] }}
                                @endif
                            </span>
                            <span>{{ $message['from'] }}</span>
                            <span>{{ $message['date'] }}</span>
                            <span>{{ $message['seen'] ? 'Read' : 'Unread' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="server-card" style="margin-top:1rem;">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">Message reader</h2>
            @if (! $messageResult || ! ($messageResult['ok'] ?? false))
                <p class="subtle">{{ $messageResult['error'] ?? 'Click a subject to open a message.' }}</p>
            @else
                @php $opened = $messageResult['message']; @endphp
                <div style="display:grid;gap:0.35rem;margin-bottom:0.8rem;">
                    <p class="subtle" style="margin:0;"><strong>Subject:</strong> {{ $opened['subject'] }}</p>
                    <p class="subtle" style="margin:0;"><strong>From:</strong> {{ $opened['from'] }}</p>
                    <p class="subtle" style="margin:0;"><strong>To:</strong> {{ $opened['to'] }}</p>
                    <p class="subtle" style="margin:0;"><strong>Date:</strong> {{ $opened['date'] }}</p>
                </div>
                <div class="server-card" style="background:rgba(15,23,42,0.25);">
                    <pre style="margin:0;white-space:pre-wrap;font-family:inherit;">{{ $opened['body'] }}</pre>
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
