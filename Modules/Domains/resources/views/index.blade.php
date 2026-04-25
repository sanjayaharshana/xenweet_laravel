@extends('layouts.host')

@section('title', 'Domains - Xenweet')

@section('content')
<div class="host-panel-scope managedb-scope">
    @if (session('success'))
        <div class="server-card" style="border-left:4px solid var(--success-border, #16a34a); margin-bottom:1rem;">
            <p class="subtle" style="margin:0; color: inherit;">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="server-card" style="border-left:4px solid var(--danger-border, #dc2626); margin-bottom:1rem;">
            <p class="subtle" style="margin:0; color: inherit;">{{ session('error') }}</p>
        </div>
    @endif
    <header class="topbar">
        <div>
            <p class="eyebrow">Domains</p>
            <h1>Domain Panel</h1>
            <p class="subtle">Manage domain records and routing tools for this hosting account.</p>
        </div>
        <div class="topbar-actions">
            <button type="button" class="btn-primary compact" id="open-add-domain-modal">Add Domain</button>
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    <p class="ssltls-workflow-eyebrow" id="domains-tabs-h">Domain tools</p>
    <nav class="managedb-tabs ssltls-tool-tabs" aria-label="Domain tools tabs" aria-describedby="domains-tabs-h">
        <a href="#" class="managedb-tab is-active">Domain</a>
        <a href="#" class="managedb-tab">Redirects</a>
        <a href="#" class="managedb-tab">Zone Editor</a>
        <a href="#" class="managedb-tab">Dynamic DNS</a>
    </nav>

    <section class="server-card">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Primary domain</h2>
        <div class="meta meta--sidebar">
            <div><span>Domain</span><strong>{{ $hosting->domain }}</strong></div>
            <div><span>Hostname</span><strong>{{ $hosting->siteHost() }}</strong></div>
            <div><span>Public URL</span><strong>{{ $hosting->publicSiteUrl() }}</strong></div>
        </div>
        <p class="subtle" style="margin-top:0.75rem; margin-bottom:0;">
            Add extra domains for this account below. They are stored in the <code>host_domains</code> table.
        </p>
    </section>

    <section class="server-card" style="margin-top:1rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Additional domains</h2>
        @if (isset($hostDomains) && $hostDomains->isNotEmpty())
            <div class="file-manager-main__sticky-head" style="margin:0.5rem 0; border-radius:8px;">
                <div class="file-row file-row-head" style="grid-template-columns: 1.2fr 0.5fr 0.5fr;">
                    <span>Domain</span>
                    <span>Type</span>
                    <span>Shared root</span>
                </div>
            </div>
            @foreach ($hostDomains as $row)
                <div class="file-row" style="grid-template-columns: 1.2fr 0.5fr 0.5fr;">
                    <span><strong>{{ $row->domain }}</strong></span>
                    <span class="subtle">{{ $row->type === 'registered' ? 'Registered' : 'Temporary' }}</span>
                    <span class="subtle">{{ $row->share_document_root ? 'Yes' : 'No' }}</span>
                </div>
            @endforeach
        @else
            <p class="subtle" style="margin:0;">No additional domains yet. Use <strong>Add Domain</strong> to create one.</p>
        @endif
    </section>

    <section class="server-card" style="margin-top:1rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Coming Next</h2>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:0.65rem;">
            <div class="host-panel-tile host-panel-tile--soon">
                <span class="host-panel-tile__icon" aria-hidden="true"><i class="fa fa-random"></i></span>
                <span class="host-panel-tile__label">Redirects</span>
                <span class="host-panel-tile__desc">Path and domain forwarding rules</span>
                <span class="host-panel-tile__badge">Soon</span>
            </div>
            <div class="host-panel-tile host-panel-tile--soon">
                <span class="host-panel-tile__icon" aria-hidden="true"><i class="fa fa-sitemap"></i></span>
                <span class="host-panel-tile__label">Zone Editor</span>
                <span class="host-panel-tile__desc">A/AAAA/CNAME/MX/TXT records</span>
                <span class="host-panel-tile__badge">Soon</span>
            </div>
            <div class="host-panel-tile host-panel-tile--soon">
                <span class="host-panel-tile__icon" aria-hidden="true"><i class="fa fa-refresh"></i></span>
                <span class="host-panel-tile__label">Dynamic DNS</span>
                <span class="host-panel-tile__desc">Auto-update host records by IP</span>
                <span class="host-panel-tile__badge">Soon</span>
            </div>
        </div>
    </section>
</div>

@php
    $openAddDomainModal = $errors->any() && old('_context') === 'add_domain';
@endphp
<div id="add-domain-modal" class="domains-modal" @if (! $openAddDomainModal) hidden @endif>
    <div class="domains-modal__backdrop" data-close-add-domain-modal></div>
    <div class="domains-modal__panel" role="dialog" aria-modal="true" aria-labelledby="add-domain-modal-title">
        <div class="domains-modal__head">
            <div class="domains-modal__title-wrap">
                <span class="domains-modal__title-icon" aria-hidden="true"><i class="fa fa-globe"></i></span>
                <div>
                    <h2 id="add-domain-modal-title">Add Domain</h2>
                    <p>Create a new domain configuration for this hosting account.</p>
                </div>
            </div>
            <button type="button" class="btn-secondary compact" data-close-add-domain-modal>Close</button>
        </div>

        <form class="domains-modal__body" id="add-domain-form" method="post" action="{{ route('hosts.domains.store', $hosting) }}">
            @csrf
            <input type="hidden" name="_context" value="add_domain">
            <div class="domains-modal__field">
                <p class="domains-modal__label">Select the type of domain to create</p>
                <div class="domains-type-grid">
                    <label class="domains-type-card">
                        <input type="radio" name="domain_type" value="temporary" @checked(old('domain_type', 'temporary') === 'temporary')>
                        <span class="domains-type-card__content">
                            <strong>Temporary Domain</strong>
                            <small>Quick setup with generated domain</small>
                        </span>
                    </label>
                    <label class="domains-type-card">
                        <input type="radio" name="domain_type" value="registered" @checked(old('domain_type') === 'registered')>
                        <span class="domains-type-card__content">
                            <strong>Registered Domain</strong>
                            <small>Use your own purchased domain</small>
                        </span>
                    </label>
                </div>
            </div>

            <div id="registered-domain-wrap" class="domains-modal__field domains-modal__field--domain" hidden>
                <div class="domains-input-header">
                    <label for="domain_name" class="domains-modal__label">Domain</label>
                    <span class="domains-input-hint">Enter your full hostname (no <code>https://</code>)</span>
                </div>
                <div class="domains-input-group">
                    <span class="domains-input-group__prefix" aria-hidden="true"><i class="fa fa-link"></i></span>
                    <input
                        id="domain_name"
                        class="domains-input"
                        name="domain_name"
                        type="text"
                        value="{{ old('domain_name') }}"
                        inputmode="url"
                        autocomplete="off"
                        placeholder="www.example.com"
                    >
                </div>
                @error('domain_name')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin: 0.45rem 0 0; font-size: 0.85rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="domains-modal__field domains-modal__field--checkbox">
                <label class="checkbox-row" style="margin: 0;">
                    <input type="checkbox" id="share_document_root" name="share_document_root" value="1" @checked(old('share_document_root'))>
                    <span>Share document root</span>
                </label>
                <p class="subtle" style="margin:0;">Keep this domain on the same web root path as the primary host.</p>
            </div>

            <div class="domains-modal__actions">
                <button type="submit" class="btn-primary">Create Domain</button>
                <button type="button" class="btn-secondary" data-close-add-domain-modal>Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
    .domains-modal {
        position: fixed;
        inset: 0;
        z-index: 80;
    }
    .domains-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
    }
    .domains-modal__panel {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 70vw;
        max-width: 980px;
        max-height: 78vh;
        overflow: auto;
        background: linear-gradient(180deg, rgba(20, 30, 52, 0.98), rgba(13, 21, 40, 0.98));
        border: 1px solid rgba(116, 142, 214, 0.35);
        border-radius: 16px;
        box-shadow: 0 18px 45px rgba(0, 0, 0, 0.4);
        padding: 1.1rem 1.1rem 1rem;
    }
    .domains-modal__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding-bottom: 0.85rem;
    }
    .domains-modal__title-wrap {
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }
    .domains-modal__title-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(79, 124, 255, 0.2);
        color: #dce8ff;
    }
    .domains-modal__head h2 {
        margin: 0;
        font-size: 1.15rem;
        line-height: 1.25;
    }
    .domains-modal__head p {
        margin: 0.18rem 0 0;
        color: var(--muted);
        font-size: 0.86rem;
    }
    .domains-modal__body {
        display: grid;
        gap: 0.9rem;
    }
    .domains-modal__field {
        border: 1px solid rgba(255, 255, 255, 0.09);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.03);
        padding: 0.8rem;
    }
    .domains-modal__field--checkbox {
        display: grid;
        gap: 0.45rem;
    }
    .domains-modal__label {
        display: block;
        margin: 0 0 0.55rem;
        font-weight: 600;
        font-size: 0.92rem;
        color: #e5eeff;
    }
    .domains-type-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
    }
    .domains-type-card {
        border: 1px solid rgba(255, 255, 255, 0.13);
        border-radius: 11px;
        background: rgba(255, 255, 255, 0.02);
        cursor: pointer;
        transition: border-color 0.2s ease, background 0.2s ease, transform 0.15s ease;
    }
    .domains-type-card:hover {
        border-color: rgba(118, 208, 255, 0.46);
        background: rgba(118, 208, 255, 0.08);
        transform: translateY(-1px);
    }
    .domains-type-card input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .domains-type-card__content {
        display: flex;
        flex-direction: column;
        gap: 0.18rem;
        padding: 0.72rem;
    }
    .domains-type-card__content strong {
        font-size: 0.92rem;
    }
    .domains-type-card__content small {
        color: var(--muted);
        font-size: 0.78rem;
        line-height: 1.25;
    }
    .domains-type-card:has(input:checked) {
        border-color: rgba(118, 208, 255, 0.62);
        background: rgba(118, 208, 255, 0.14);
        box-shadow: inset 0 0 0 1px rgba(118, 208, 255, 0.32);
    }
    .domains-modal__field--domain {
        position: relative;
    }
    .domains-input-header {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.4rem 0.75rem;
        margin-bottom: 0.55rem;
    }
    .domains-input-header .domains-modal__label {
        margin: 0;
    }
    .domains-input-hint {
        font-size: 0.76rem;
        color: var(--muted);
    }
    .domains-input-hint code {
        font-size: 0.72em;
        padding: 0.1em 0.35em;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.08);
    }
    .domains-input-group {
        display: flex;
        align-items: stretch;
        border-radius: 12px;
        border: 1px solid rgba(118, 208, 255, 0.28);
        background: linear-gradient(180deg, rgba(8, 14, 32, 0.85), rgba(5, 10, 24, 0.92));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05), 0 4px 16px rgba(0, 0, 0, 0.18);
        overflow: hidden;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .domains-input-group:focus-within {
        border-color: rgba(118, 208, 255, 0.6);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.08),
            0 0 0 2px rgba(79, 124, 255, 0.22);
    }
    .domains-input-group__prefix {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 0.8rem;
        min-width: 2.5rem;
        color: #9eb7f0;
        background: linear-gradient(180deg, rgba(60, 90, 200, 0.18), rgba(40, 60, 140, 0.1));
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 0.95rem;
    }
    .domains-input {
        flex: 1 1 auto;
        min-width: 0;
        border: none;
        background: transparent;
        color: #e8f0ff;
        font-size: 0.98rem;
        font-weight: 500;
        letter-spacing: 0.01em;
        padding: 0.7rem 0.85rem 0.7rem 0.35rem;
        outline: none;
    }
    .domains-input::placeholder {
        color: rgba(200, 215, 255, 0.4);
        font-weight: 400;
    }
    .domains-modal__actions {
        display: flex;
        gap: 0.6rem;
        margin-top: 0.1rem;
        padding-top: 0.35rem;
    }
    @media (max-width: 900px) {
        .domains-modal__panel {
            width: 92vw;
        }
        .domains-type-grid {
            grid-template-columns: 1fr;
        }
    }
    html[data-theme="light"] .domains-modal__panel {
        background: linear-gradient(180deg, #f8fbff, #edf4ff);
        border-color: rgba(45, 92, 190, 0.25);
        box-shadow: 0 18px 35px rgba(15, 28, 58, 0.2);
    }
    html[data-theme="light"] .domains-modal__head {
        border-bottom-color: rgba(15, 23, 42, 0.12);
    }
    html[data-theme="light"] .domains-modal__title-icon {
        color: #2051b7;
        background: rgba(59, 91, 219, 0.12);
    }
    html[data-theme="light"] .domains-modal__label,
    html[data-theme="light"] .domains-modal__head h2 {
        color: #13213a;
    }
    html[data-theme="light"] .domains-modal__head p {
        color: #4f607d;
    }
    html[data-theme="light"] .domains-modal__field {
        border-color: rgba(15, 23, 42, 0.1);
        background: rgba(255, 255, 255, 0.68);
    }
    html[data-theme="light"] .domains-type-card {
        border-color: rgba(15, 23, 42, 0.15);
        background: rgba(255, 255, 255, 0.85);
    }
    html[data-theme="light"] .domains-type-card__content small {
        color: #5a6b88;
    }
    html[data-theme="light"] .domains-type-card:has(input:checked) {
        border-color: rgba(59, 91, 219, 0.5);
        background: rgba(59, 91, 219, 0.12);
        box-shadow: inset 0 0 0 1px rgba(59, 91, 219, 0.18);
    }
    html[data-theme="light"] .domains-input-hint code {
        background: rgba(15, 23, 42, 0.06);
    }
    html[data-theme="light"] .domains-input-group {
        border-color: rgba(45, 92, 190, 0.28);
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(15, 28, 58, 0.06);
    }
    html[data-theme="light"] .domains-input-group__prefix {
        color: #3b5bdb;
        background: rgba(59, 91, 219, 0.08);
        border-right-color: rgba(15, 23, 42, 0.08);
    }
    html[data-theme="light"] .domains-input {
        color: #0f172a;
    }
    html[data-theme="light"] .domains-input::placeholder {
        color: #94a3b8;
    }
</style>

<script>
    (function () {
        var openBtn = document.getElementById('open-add-domain-modal');
        var modal = document.getElementById('add-domain-modal');
        var closeBtns = document.querySelectorAll('[data-close-add-domain-modal]');
        var typeInputs = document.querySelectorAll('input[name="domain_type"]');
        var registeredWrap = document.getElementById('registered-domain-wrap');
        var domainInput = document.getElementById('domain_name');
        var form = document.getElementById('add-domain-form');

        function selectedType() {
            var checked = document.querySelector('input[name="domain_type"]:checked');
            return checked ? checked.value : 'temporary';
        }

        function syncTypeUi() {
            var isRegistered = selectedType() === 'registered';
            if (registeredWrap) {
                registeredWrap.hidden = !isRegistered;
            }
            if (domainInput) {
                domainInput.required = !!isRegistered;
            }
        }

        function openModal() {
            if (!modal) return;
            modal.hidden = false;
            syncTypeUi();
        }

        function closeModal() {
            if (!modal) return;
            modal.hidden = true;
        }

        if (openBtn) {
            openBtn.addEventListener('click', openModal);
        }

        closeBtns.forEach(function (btn) {
            btn.addEventListener('click', closeModal);
        });

        typeInputs.forEach(function (input) {
            input.addEventListener('change', syncTypeUi);
        });
        syncTypeUi();

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal && !modal.hidden) {
                closeModal();
            }
        });

    })();
</script>
@endsection
