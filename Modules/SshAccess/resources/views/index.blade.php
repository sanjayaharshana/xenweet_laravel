@extends('layouts.host')

@section('title', 'SSH Access - Xenweet')

@section('content')
<div class="host-panel-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Security</p>
            <h1>SSH Access</h1>
            <p class="subtle">Connection details and command templates for this host.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    @if (session('sshaccess_success'))
        <div class="flash-success managedb-flash" role="status">{{ session('sshaccess_success') }}</div>
    @endif
    @if (session('sshaccess_error'))
        <div class="alert error managedb-flash" role="alert">{{ session('sshaccess_error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert error managedb-flash" role="alert">
            @foreach ($errors->all() as $message)
                <p style="margin:0 0 0.35rem;">{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <p class="ssltls-workflow-eyebrow" id="ssh-tabs-h">Host panel tabs</p>
    <nav class="managedb-tabs ssltls-tool-tabs" aria-label="Host panel tabs" aria-describedby="ssh-tabs-h">
        <a href="{{ route('hosts.panel', $hosting) }}" class="managedb-tab">
            <i class="fa fa-th-large" aria-hidden="true"></i> Overview
        </a>
        <a href="{{ route('hosts.terminal', $hosting) }}" class="managedb-tab">
            <i class="fa fa-terminal" aria-hidden="true"></i> Terminal
        </a>
        <span class="managedb-tab is-active" aria-current="page">
            <i class="fa fa-lock" aria-hidden="true"></i> SSH Access
        </span>
    </nav>

    <section class="server-card" aria-labelledby="ssh-target-h">
        <h2 id="ssh-target-h">Target</h2>
        <div class="meta" style="margin-top:0.35rem;">
            <div><span>Host</span><strong>{{ $sshHost }}</strong></div>
            <div><span>User</span><strong>{{ $sshUser }}</strong></div>
            <div><span>Port</span><strong>{{ $sshPort }}</strong></div>
        </div>
    </section>

    <section class="server-card" aria-labelledby="ssh-quick-h" style="margin-top:0.8rem;">
        <h2 id="ssh-quick-h">Quick commands</h2>
        <p class="subtle" style="margin:0.2rem 0 0.55rem;">Copy and run in your terminal. Use SSH keys in production and avoid password login when possible.</p>

        <label for="ssh-command" style="display:block; margin-bottom:0.25rem;">SSH login</label>
        <textarea id="ssh-command" class="managedb-flow-tooltip" readonly style="width:100%; min-height:2.6rem; resize:vertical;">{{ $sshCommand }}</textarea>

        <label for="scp-command" style="display:block; margin:0.65rem 0 0.25rem;">Upload one file (SCP)</label>
        <textarea id="scp-command" class="managedb-flow-tooltip" readonly style="width:100%; min-height:2.6rem; resize:vertical;">{{ $scpCommand }}</textarea>

        <label for="rsync-command" style="display:block; margin:0.65rem 0 0.25rem;">Sync directory (rsync)</label>
        <textarea id="rsync-command" class="managedb-flow-tooltip" readonly style="width:100%; min-height:2.6rem; resize:vertical;">{{ $rsyncCommand }}</textarea>
    </section>

    <section class="server-card" aria-labelledby="ssh-notes-h" style="margin-top:0.8rem;">
        <h2 id="ssh-notes-h">Notes</h2>
        <ul class="ssltls-checklist" style="margin-top:0.4rem;">
            <li>Confirm SSH is allowed in your server firewall for port <strong>{{ $sshPort }}</strong>.</li>
            <li>If login fails, verify the Linux user exists and has shell access.</li>
            <li>Recommended: add your public key to <code>~/.ssh/authorized_keys</code> and disable password auth.</li>
        </ul>
    </section>

    <section class="server-card" aria-labelledby="ssh-create-h" style="margin-top:0.8rem;">
        <h2 id="ssh-create-h">Create SSH Account (jailed)</h2>
        <p class="subtle" style="margin:0.2rem 0 0.55rem;">
            Creates a Linux user with home under this hosting root and shell <code>/bin/rbash</code> (restricted shell).
        </p>
        <form method="POST" action="{{ $sshCreateUrl }}" class="managedb-form">
            @csrf
            <label for="ssh-acc-username">Username</label>
            <input
                id="ssh-acc-username"
                type="text"
                name="username"
                required
                minlength="3"
                maxlength="32"
                pattern="[a-z_][a-z0-9_-]{2,31}"
                autocomplete="off"
                value="{{ old('username') }}"
                placeholder="clientssh"
            >
            <label for="ssh-acc-password">Password</label>
            <input
                id="ssh-acc-password"
                type="password"
                name="password"
                required
                minlength="8"
                maxlength="128"
                autocomplete="new-password"
            >
            <label for="ssh-acc-pubkey">Public key (optional)</label>
            <textarea
                id="ssh-acc-pubkey"
                name="public_key"
                rows="4"
                spellcheck="false"
                placeholder="ssh-ed25519 AAAAC3... user@laptop"
            >{{ old('public_key') }}</textarea>
            <div class="managedb-actions">
                <button type="submit" class="btn-primary">Create jailed SSH account</button>
            </div>
        </form>
    </section>
</div>
@endsection
