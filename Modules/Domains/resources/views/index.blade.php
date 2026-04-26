@extends('layouts.host')

@section('title', 'Domains - Xenweet')

@section('content')
@php
    $activeTab = match (request('tab')) {
        'redirects' => 'redirects',
        'zone' => 'zone',
        default => 'domain',
    };
    $openAddDomainModal = $errors->any() && old('_context') === 'add_domain';
    $openRedirectModal = $errors->any() && old('_context') === 'add_redirect';
    $hasFileManagerRoute = \Nwidart\Modules\Facades\Module::isEnabled('FileManager') && \Illuminate\Support\Facades\Route::has('hosts.files.index');
    $formatRootPath = static function (?string $path, ?string $hostRoot = null): string {
        $path = trim((string) $path);
        if ($path === '') {
            return '-';
        }

        $display = $path;
        if ($hostRoot !== null) {
            $hostRoot = rtrim(trim($hostRoot), '/');
            if ($hostRoot !== '' && str_starts_with($path, $hostRoot)) {
                $relative = ltrim(substr($path, strlen($hostRoot)), '/');
                $display = $relative !== '' ? $relative : basename($path);
            }
        }

        return $display;
    };
    $rootPathToFileManagerRelative = static function (?string $path, ?string $hostRoot = null): ?string {
        $path = trim((string) $path);
        $hostRoot = rtrim(trim((string) $hostRoot), '/');
        if ($path === '' || $hostRoot === '') {
            return null;
        }
        if (! str_starts_with($path, $hostRoot)) {
            return null;
        }

        return ltrim(substr($path, strlen($hostRoot)), '/');
    };
    $primaryRootPathShown = $formatRootPath($hosting->web_root_path, $hosting->host_root_path);
    $primaryRootPathFm = $rootPathToFileManagerRelative($hosting->web_root_path, $hosting->host_root_path);
    $redirectDomainOptions = collect([$hosting->siteHost()]);
    if (isset($hostDomains)) {
        $redirectDomainOptions = $redirectDomainOptions
            ->merge($hostDomains->pluck('domain'))
            ->filter(fn ($d) => trim((string) $d) !== '')
            ->unique()
            ->values();
    }
    $zoneDomainOptions = $redirectDomainOptions;
@endphp

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
        <a href="{{ route('hosts.domains.index', ['hosting' => $hosting, 'tab' => 'domain']) }}" class="managedb-tab {{ $activeTab === 'domain' ? 'is-active' : '' }}">Domain</a>
        <a href="{{ route('hosts.domains.index', ['hosting' => $hosting, 'tab' => 'redirects']) }}" class="managedb-tab {{ $activeTab === 'redirects' ? 'is-active' : '' }}">Redirects</a>
        <a href="{{ route('hosts.domains.index', ['hosting' => $hosting, 'tab' => 'zone']) }}" class="managedb-tab {{ $activeTab === 'zone' ? 'is-active' : '' }}">Zone Editor</a>
        <a href="#" class="managedb-tab" onclick="return false;" title="Coming soon">Dynamic DNS</a>
    </nav>
@if ($activeTab === 'domain')
    <section class="server-card">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Primary domain</h2>
        <div class="meta meta--sidebar">
            <div><span>Domain</span><strong>{{ $hosting->domain }}</strong></div>
            <div><span>Hostname</span><strong>{{ $hosting->siteHost() }}</strong></div>
            <div><span>Public URL</span><strong>{{ $hosting->publicSiteUrl() }}</strong></div>
            <div>
                <span>Document Root</span>
                <strong title="{{ $hosting->web_root_path }}">
                    @if ($hasFileManagerRoute && $primaryRootPathFm !== null)
                        <a href="{{ route('hosts.files.index', ['hosting' => $hosting, 'path' => $primaryRootPathFm]) }}" target="_blank" rel="noopener noreferrer">{{ $primaryRootPathShown }}</a>
                    @else
                        {{ $primaryRootPathShown }}
                    @endif
                </strong>
            </div>
        </div>
        <p class="subtle" style="margin-top:0.75rem; margin-bottom:0;">
            Add extra domains for this account below. They are stored in the <code>host_domains</code> table.
        </p>
    </section>

    <section class="server-card" style="margin-top:1rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Additional domains</h2>
        @if (isset($hostDomains) && $hostDomains->isNotEmpty())
            <div class="file-manager-main__sticky-head" style="margin:0.5rem 0; border-radius:8px;">
                <div class="file-row file-row-head" style="grid-template-columns: 1.2fr 0.5fr 0.5fr 1.2fr 0.5fr;">
                    <span>Domain</span>
                    <span>Type</span>
                    <span>Root mode</span>
                    <span>Document root path</span>
                    <span>Action</span>
                </div>
            </div>
            @foreach ($hostDomains as $row)
                <div class="file-row" style="grid-template-columns: 1.2fr 0.5fr 0.5fr 1.2fr 0.5fr;">
                    <span><strong>{{ $row->domain }}</strong></span>
                    <span class="subtle">{{ $row->type === 'registered' ? 'Registered' : 'Temporary' }}</span>
                    <span class="subtle">{{ $row->share_document_root ? 'Shared' : 'Custom' }}</span>
                    @php
                        $rawRootPath = $row->share_document_root ? $hosting->web_root_path : $row->document_root;
                        $shownRootPath = $formatRootPath($rawRootPath, $hosting->host_root_path);
                        $fmPath = $rootPathToFileManagerRelative($rawRootPath, $hosting->host_root_path);
                    @endphp
                    <span class="subtle" title="{{ $rawRootPath }}">
                        @if ($hasFileManagerRoute && $fmPath !== null)
                            <a href="{{ route('hosts.files.index', ['hosting' => $hosting, 'path' => $fmPath]) }}" target="_blank" rel="noopener noreferrer">{{ $shownRootPath }}</a>
                        @else
                            {{ $shownRootPath }}
                        @endif
                    </span>
                    <span>
                        <form method="post" action="{{ route('hosts.domains.destroy', [$hosting, $row]) }}" onsubmit="return confirm('Delete domain {{ $row->domain }}? This will also reapply Nginx.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-secondary compact" style="border-color: rgba(220, 38, 38, 0.45); color: #fecaca;">Delete</button>
                        </form>
                    </span>
                </div>
            @endforeach
        @else
            <p class="subtle" style="margin:0;">No additional domains yet. Use <strong>Add Domain</strong> to create one.</p>
        @endif
    </section>

    <section class="server-card" style="margin-top:1rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">More tools</h2>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:0.65rem;">
            <div class="host-panel-tile host-panel-tile--soon">
                <span class="host-panel-tile__icon" aria-hidden="true"><i class="fa fa-random"></i></span>
                <span class="host-panel-tile__label">Redirects</span>
                <span class="host-panel-tile__desc">Path and domain forwarding rules</span>
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
@elseif ($activeTab === 'redirects')
    <section class="server-card" style="margin-top:1rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem;">
            <h2 class="host-sidebar-meta-title" style="margin:0;">Domain redirects</h2>
            <button type="button" class="btn-primary compact" id="open-add-redirect-modal">Add Redirect</button>
        </div>
        <p class="subtle" style="margin-top:0.5rem;">Choose a domain from this hosting account and forward it to a target URL (301 or 302).</p>
        @if (isset($redirects) && $redirects->isNotEmpty())
            <div class="file-manager-main__sticky-head" style="margin:0.5rem 0; border-radius:8px;">
                <div class="file-row file-row-head" style="grid-template-columns: 1fr 0.5fr 1.2fr 0.5fr;">
                    <span>Source domain</span>
                    <span>Type</span>
                    <span>Target URL</span>
                    <span>Action</span>
                </div>
            </div>
            @foreach ($redirects as $r)
                <div class="file-row" style="grid-template-columns: 1fr 0.5fr 1.2fr 0.5fr;">
                    <span><strong>{{ $r->source_domain }}</strong></span>
                    <span class="subtle">{{ $r->redirect_type === 'permanent' ? '301' : '302' }}</span>
                    <span class="subtle"><a href="{{ $r->redirect_url }}" target="_blank" rel="noopener noreferrer">{{ $r->redirect_url }}</a></span>
                    <span>
                        <form method="post" action="{{ route('hosts.domains.redirects.destroy', [$hosting, $r]) }}" onsubmit="return confirm('Delete redirect for {{ $r->source_domain }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-secondary compact" style="border-color: rgba(220, 38, 38, 0.45); color: #fecaca;">Delete</button>
                        </form>
                    </span>
                </div>
            @endforeach
        @else
            <p class="subtle" style="margin:0.35rem 0 0;">No redirects yet. Click <strong>Add Redirect</strong> to create one.</p>
        @endif
    </section>
@elseif ($activeTab === 'zone')
    <section class="server-card" style="margin-top:0;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Zone Editor</h2>
        <p class="subtle" style="margin:0.35rem 0 0.9rem;">Plan DNS for your domains. Records are stored here; apply the same at your domain registrar or DNS host when you point nameservers (full automation is not active yet).</p>

        <form class="domains-modal__body" method="post" action="{{ route('hosts.domains.zone-records.store', $hosting) }}" style="margin:0; gap:0.75rem; border:1px solid rgba(255,255,255,0.09); border-radius:12px; padding:0.8rem; background: rgba(255,255,255,0.03);">
            @csrf
            <input type="hidden" name="_context" value="add_zone">
            @if (! empty($filterZone))
                <input type="hidden" name="return_filter_zone" value="{{ $filterZone }}">
            @endif
            <div class="domains-type-grid" style="margin-bottom:0.5rem;">
                <div class="domains-modal__field" style="margin:0; padding:0.5rem 0.65rem;">
                    <label class="domains-modal__label" for="zone_domain" style="margin-bottom:0.35rem;">Zone (domain)</label>
                    <select id="zone_domain" name="zone_domain" class="domains-input" style="width:100%; background: transparent;" required>
                        @foreach ($zoneDomainOptions as $d)
                            <option value="{{ $d }}" @selected(old('zone_domain', $hosting->siteHost()) === $d)>{{ $d }}</option>
                        @endforeach
                    </select>
                    @error('zone_domain')
                        <p class="subtle" style="color: var(--danger-text, #b91c1c); margin: 0.35rem 0 0; font-size: 0.85rem;">{{ $message }}</p>
                    @enderror
                </div>
                <div class="domains-modal__field" style="margin:0; padding:0.5rem 0.65rem;">
                    <label class="domains-modal__label" for="record_type" style="margin-bottom:0.35rem;">Type</label>
                    <select id="record_type" name="record_type" class="domains-input" style="width:100%; background: transparent;" required>
                        <option value="A" @selected(old('record_type', 'A') === 'A')>A</option>
                        <option value="AAAA" @selected(old('record_type') === 'AAAA')>AAAA</option>
                        <option value="CNAME" @selected(old('record_type') === 'CNAME')>CNAME</option>
                        <option value="MX" @selected(old('record_type') === 'MX')>MX</option>
                        <option value="TXT" @selected(old('record_type') === 'TXT')>TXT</option>
                    </select>
                </div>
            </div>
            <div class="domains-type-grid" style="margin-bottom:0.5rem;">
                <div class="domains-modal__field" style="margin:0; padding:0.5rem 0.65rem;">
                    <label class="domains-modal__label" for="record_name" style="margin-bottom:0.35rem;">Name / host</label>
                    <div class="domains-input-group">
                        <span class="domains-input-group__prefix" aria-hidden="true"><i class="fa fa-at"></i></span>
                        <input id="record_name" class="domains-input" name="record_name" type="text" value="{{ old('record_name', '@') }}" placeholder="@" autocomplete="off" required>
                    </div>
                    @error('record_name')
                        <p class="subtle" style="color: var(--danger-text, #b91c1c); margin: 0.35rem 0 0; font-size: 0.85rem;">{{ $message }}</p>
                    @enderror
                </div>
                <div class="domains-modal__field" id="zone-mx-priority-wrap" style="margin:0; padding:0.5rem 0.65rem; @if (old('record_type', 'A') !== 'MX') display:none; @endif">
                    <label class="domains-modal__label" for="mx_priority" style="margin-bottom:0.35rem;">MX priority</label>
                    <div class="domains-input-group">
                        <span class="domains-input-group__prefix" aria-hidden="true"><i class="fa fa-sort-numeric-asc"></i></span>
                        <input id="mx_priority" class="domains-input" name="mx_priority" type="number" min="0" max="65535" value="{{ old('mx_priority', '10') }}" placeholder="10">
                    </div>
                    @error('mx_priority')
                        <p class="subtle" style="color: var(--danger-text, #b91c1c); margin: 0.35rem 0 0; font-size: 0.85rem;">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="domains-modal__field" style="margin:0; padding:0.5rem 0.65rem;">
                <label class="domains-modal__label" for="record_value" style="margin-bottom:0.35rem;">Value</label>
                <div class="domains-input-group">
                    <span class="domains-input-group__prefix" aria-hidden="true"><i class="fa fa-database"></i></span>
                    <input id="record_value" class="domains-input" name="record_value" type="text" value="{{ old('record_value') }}" placeholder="e.g. 198.51.100.1 or target.example.com" required>
                </div>
                @error('record_value')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin: 0.35rem 0 0; font-size: 0.85rem;">{{ $message }}</p>
                @enderror
            </div>
            <div class="domains-modal__field" style="margin:0; padding:0.5rem 0.65rem; max-width: 12rem;">
                <label class="domains-modal__label" for="ttl" style="margin-bottom:0.35rem;">TTL (seconds)</label>
                <input id="ttl" class="domains-input" name="ttl" type="number" min="60" max="86400" value="{{ old('ttl', '3600') }}" style="width:100%; border-radius:8px; border:1px solid rgba(118, 208, 255, 0.28); padding:0.45rem 0.6rem; background: transparent;">
            </div>
            <div style="display:flex; gap:0.5rem; margin-top:0.5rem; padding:0 0.65rem 0.5rem;">
                <button type="submit" class="btn-primary">Add record</button>
            </div>
        </form>
    </section>

    <section class="server-card" style="margin-top:1rem;">
        <h2 class="host-sidebar-meta-title" style="margin-top:0;">Current records
            @if (($hasZoneTable ?? false) && isset($zoneRecords) && $zoneRecords->isNotEmpty())
                <span class="subtle" style="font-weight:500; font-size:0.85em;">({{ $zoneRecords->count() }})</span>
            @endif
        </h2>

        @if (! ($hasZoneTable ?? false))
            <p class="subtle" style="margin:0;">Zone records are not available until the database is migrated. On the server run: <code style="font-size:0.85em;">php artisan migrate</code></p>
        @else
            <form method="get" action="{{ route('hosts.domains.index', $hosting) }}" class="domains-input-header" style="margin:0.35rem 0 0.75rem; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="zone">
                <label for="filter_zone" class="subtle" style="margin:0; font-size:0.8rem;">Show zone</label>
                <select id="filter_zone" name="filter_zone" class="btn-secondary compact" style="min-width: 12rem; padding:0.35rem 0.6rem; cursor:pointer;" onchange="this.form.submit()">
                    <option value="">{{ '— All zones —' }}</option>
                    @foreach ($zoneDomainOptions as $d)
                        <option value="{{ $d }}" @selected(($filterZone ?? null) === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </form>

            @if (isset($zoneRecords) && $zoneRecords->isNotEmpty())
            <div class="file-manager-main__sticky-head" style="margin:0.5rem 0; border-radius:8px;">
                <div class="file-row file-row-head" style="grid-template-columns: 1fr 0.4fr 0.4fr 1.2fr 0.4fr 0.5fr 0.4fr;">
                    <span>Zone</span>
                    <span>Name</span>
                    <span>Type</span>
                    <span>Value</span>
                    <span>Priority</span>
                    <span>TTL</span>
                    <span></span>
                </div>
            </div>
            @foreach ($zoneRecords as $zr)
                <div class="file-row" style="grid-template-columns: 1fr 0.4fr 0.4fr 1.2fr 0.4fr 0.5fr 0.4fr;">
                    <span class="subtle">{{ $zr->zone_domain }}</span>
                    <span><strong>{{ $zr->record_name }}</strong></span>
                    <span class="subtle">{{ $zr->record_type }}</span>
                    <span class="subtle" style="word-break: break-all;">{{ $zr->record_value }}</span>
                    <span class="subtle">{{ $zr->record_type === 'MX' && $zr->mx_priority !== null ? (string) $zr->mx_priority : '—' }}</span>
                    <span class="subtle">{{ (string) $zr->ttl }}</span>
                    <span>
                        <form method="post" action="{{ route('hosts.domains.zone-records.destroy', [$hosting, $zr]) }}" onsubmit="return confirm('Delete this DNS record?');" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            @if (! empty($filterZone))
                                <input type="hidden" name="return_filter_zone" value="{{ $filterZone }}">
                            @endif
                            <button type="submit" class="btn-secondary compact" style="border-color: rgba(220, 38, 38, 0.45); color: #fecaca;">Delete</button>
                        </form>
                    </span>
                </div>
            @endforeach
            @else
            <p class="subtle" style="margin:0;">@if (! empty($filterZone))
                No records for <strong>{{ $filterZone }}</strong>. @else
                No zone records yet. @endif Add one using the form above.</p>
            @endif
        @endif
    </section>
@endif
</div>

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

            <div class="domains-modal__field">
                <p class="domains-modal__label">Document root mode</p>
                <div class="domains-type-grid">
                    <label class="domains-type-card">
                        <input type="radio" name="root_mode" value="shared" @checked(old('root_mode', 'shared') === 'shared')>
                        <span class="domains-type-card__content">
                            <strong>Shared Root</strong>
                            <small>Use the same root path as the primary host</small>
                        </span>
                    </label>
                    <label class="domains-type-card">
                        <input type="radio" name="root_mode" value="custom" @checked(old('root_mode') === 'custom')>
                        <span class="domains-type-card__content">
                            <strong>Custom Root</strong>
                            <small>Use your own absolute path for this domain</small>
                        </span>
                    </label>
                </div>
                <p class="subtle" style="margin:0.5rem 0 0;">Primary root path: <code>{{ $formatRootPath($hosting->web_root_path, $hosting->host_root_path) }}</code></p>
            </div>

            <div id="custom-root-wrap" class="domains-modal__field domains-modal__field--domain" hidden>
                <div class="domains-input-header">
                    <label for="document_root" class="domains-modal__label">Custom root path</label>
                    <span class="domains-input-hint">Absolute server path starting with <code>/</code></span>
                </div>
                <div class="domains-input-group">
                    <span class="domains-input-group__prefix" aria-hidden="true"><i class="fa fa-folder-open"></i></span>
                    <input
                        id="document_root"
                        class="domains-input"
                        name="document_root"
                        type="text"
                        value="{{ old('document_root') }}"
                        autocomplete="off"
                        placeholder="{{ $formatRootPath($hosting->web_root_path, $hosting->host_root_path) }}"
                    >
                </div>
                @error('document_root')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin: 0.45rem 0 0; font-size: 0.85rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="domains-modal__actions">
                <button type="submit" class="btn-primary">Create Domain</button>
                <button type="button" class="btn-secondary" data-close-add-domain-modal>Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="add-redirect-modal" class="domains-modal" @if (! $openRedirectModal) hidden @endif>
    <div class="domains-modal__backdrop" data-close-add-redirect-modal></div>
    <div class="domains-modal__panel" role="dialog" aria-modal="true" aria-labelledby="add-redirect-modal-title">
        <div class="domains-modal__head">
            <div class="domains-modal__title-wrap">
                <span class="domains-modal__title-icon" aria-hidden="true"><i class="fa fa-random"></i></span>
                <div>
                    <h2 id="add-redirect-modal-title">Add Redirect</h2>
                    <p>Select source domain, redirect type and target URL.</p>
                </div>
            </div>
            <button type="button" class="btn-secondary compact" data-close-add-redirect-modal>Close</button>
        </div>

        <form class="domains-modal__body" method="post" action="{{ route('hosts.domains.redirects.store', $hosting) }}">
            @csrf
            <input type="hidden" name="_context" value="add_redirect">
            <div class="domains-modal__field">
                <label class="domains-modal__label" for="source_domain">Select domain</label>
                <select id="source_domain" class="domains-input" name="source_domain" style="width:100%; background: transparent;">
                    @foreach ($redirectDomainOptions as $d)
                        <option value="{{ $d }}" @selected(old('source_domain') === $d)>{{ $d }}</option>
                    @endforeach
                </select>
                @error('source_domain')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin: 0.45rem 0 0; font-size: 0.85rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="domains-modal__field">
                <p class="domains-modal__label">Redirect type</p>
                <div class="domains-type-grid">
                    <label class="domains-type-card">
                        <input type="radio" name="redirect_type" value="temporary" @checked(old('redirect_type', 'temporary') === 'temporary')>
                        <span class="domains-type-card__content">
                            <strong>Temporary Redirect</strong>
                            <small>302 (can change later)</small>
                        </span>
                    </label>
                    <label class="domains-type-card">
                        <input type="radio" name="redirect_type" value="permanent" @checked(old('redirect_type') === 'permanent')>
                        <span class="domains-type-card__content">
                            <strong>Permanent Redirect</strong>
                            <small>301 (final/canonical)</small>
                        </span>
                    </label>
                </div>
                @error('redirect_type')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin: 0.45rem 0 0; font-size: 0.85rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="domains-modal__field domains-modal__field--domain">
                <div class="domains-input-header">
                    <label for="redirect_url" class="domains-modal__label">Redirect URL</label>
                    <span class="domains-input-hint">Example: <code>https://example.com/path</code></span>
                </div>
                <div class="domains-input-group">
                    <span class="domains-input-group__prefix" aria-hidden="true"><i class="fa fa-external-link"></i></span>
                    <input id="redirect_url" class="domains-input" name="redirect_url" type="url" value="{{ old('redirect_url') }}" placeholder="https://example.com" required>
                </div>
                @error('redirect_url')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin: 0.45rem 0 0; font-size: 0.85rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="domains-modal__actions">
                <button type="submit" class="btn-primary">Save Redirect</button>
                <button type="button" class="btn-secondary" data-close-add-redirect-modal>Cancel</button>
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
        var openRedirectBtn = document.getElementById('open-add-redirect-modal');
        var redirectModal = document.getElementById('add-redirect-modal');
        var closeRedirectBtns = document.querySelectorAll('[data-close-add-redirect-modal]');
        var typeInputs = document.querySelectorAll('input[name="domain_type"]');
        var rootModeInputs = document.querySelectorAll('input[name="root_mode"]');
        var registeredWrap = document.getElementById('registered-domain-wrap');
        var domainInput = document.getElementById('domain_name');
        var customRootWrap = document.getElementById('custom-root-wrap');
        var customRootInput = document.getElementById('document_root');
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

        function selectedRootMode() {
            var checked = document.querySelector('input[name="root_mode"]:checked');
            return checked ? checked.value : 'shared';
        }

        function syncRootUi() {
            var isCustom = selectedRootMode() === 'custom';
            if (customRootWrap) {
                customRootWrap.hidden = !isCustom;
            }
            if (customRootInput) {
                customRootInput.required = !!isCustom;
            }
        }

        function openModal() {
            if (!modal) return;
            modal.hidden = false;
            syncTypeUi();
            syncRootUi();
        }

        function closeModal() {
            if (!modal) return;
            modal.hidden = true;
        }
        function openRedirectModal() {
            if (!redirectModal) return;
            redirectModal.hidden = false;
        }
        function closeRedirectModal() {
            if (!redirectModal) return;
            redirectModal.hidden = true;
        }

        if (openBtn) {
            openBtn.addEventListener('click', openModal);
        }

        closeBtns.forEach(function (btn) {
            btn.addEventListener('click', closeModal);
        });
        if (openRedirectBtn) {
            openRedirectBtn.addEventListener('click', openRedirectModal);
        }
        closeRedirectBtns.forEach(function (btn) {
            btn.addEventListener('click', closeRedirectModal);
        });

        typeInputs.forEach(function (input) {
            input.addEventListener('change', syncTypeUi);
        });
        rootModeInputs.forEach(function (input) {
            input.addEventListener('change', syncRootUi);
        });
        syncTypeUi();
        syncRootUi();

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal && !modal.hidden) {
                closeModal();
            }
            if (event.key === 'Escape' && redirectModal && !redirectModal.hidden) {
                closeRedirectModal();
            }
        });

    })();
</script>
<script>
    (function () {
        var recordType = document.getElementById('record_type');
        var mxWrap = document.getElementById('zone-mx-priority-wrap');
        var mxInput = document.getElementById('mx_priority');
        if (!recordType || !mxWrap) {
            return;
        }
        function sync() {
            var isMx = recordType.value === 'MX';
            mxWrap.style.display = isMx ? 'block' : 'none';
            if (mxInput) {
                mxInput.disabled = !isMx;
            }
        }
        recordType.addEventListener('change', sync);
        sync();
    })();
</script>
@endsection
