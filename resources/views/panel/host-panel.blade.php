@extends('layouts.host')

@section('title', 'Host Panel - Xenweet')

@section('content')
<div class="host-panel-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Host Panel</p>
            <h1>{{ $hosting->domain }}</h1>
            <p class="subtle">Applications and tools for this hosting account.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary compact" href="{{ $hosting->publicSiteUrl() }}" target="_blank" rel="noopener noreferrer">Open Site</a>
            <a class="btn-secondary compact subtle-link" href="http://{{ $hosting->server_ip }}" target="_blank" rel="noopener noreferrer" title="Direct IP">Open IP</a>
        </div>
    </header>

    <div class="host-panel-apps">
        @foreach ($hostPanelCategories as $category)
            <section class="host-panel-category" aria-labelledby="host-cat-{{ $category['id'] ?? $loop->index }}">
                <h2 class="host-panel-category__title" id="host-cat-{{ $category['id'] ?? $loop->index }}">{{ $category['title'] }}</h2>
                <div class="host-panel-tiles">
                    @foreach ($category['items'] as $item)
                        @if (! empty($item['href']))
                            <a
                                href="{{ $item['href'] }}"
                                class="host-panel-tile host-panel-tile--link"
                                @if (! empty($item['target'])) target="{{ $item['target'] }}" @endif
                                @if (($item['target'] ?? '') === '_blank') rel="noopener noreferrer" @endif
                            >
                                <span class="host-panel-tile__icon" aria-hidden="true"><i class="{{ $item['icon'] ?? 'fa fa-circle-o' }}"></i></span>
                                <span class="host-panel-tile__label">{{ $item['label'] }}</span>
                                @if (! empty($item['description']))
                                    <span class="host-panel-tile__desc">{{ $item['description'] }}</span>
                                @endif
                            </a>
                        @else
                            <span class="host-panel-tile host-panel-tile--soon" title="Configure URL or route in config/host_panel.php">
                                <span class="host-panel-tile__icon" aria-hidden="true"><i class="{{ $item['icon'] ?? 'fa fa-circle-o' }}"></i></span>
                                <span class="host-panel-tile__label">{{ $item['label'] }}</span>
                                @if (! empty($item['description']))
                                    <span class="host-panel-tile__desc">{{ $item['description'] }}</span>
                                @endif
                                <span class="host-panel-tile__badge">Soon</span>
                            </span>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
@endsection

@section('right_sidebar')
<div class="host-panel-scope">
    <div class="host-panel-sidebar">
        <div class="server-card host-sidebar-meta">
            <h2 class="host-sidebar-meta-title">Host details</h2>
            <div class="meta meta--sidebar">
                <div><span>Domain</span><strong>{{ $hosting->domain }}</strong></div>
                <div><span>Server IP</span><strong>{{ $hosting->server_ip }}</strong></div>
                <div><span>Web root</span><strong>{{ $hosting->web_root_path ?: 'Not assigned yet' }}</strong></div>
            </div>
        </div>

        <div class="tips-panel tips-panel--nested">
            <h2>Tips</h2>
            <p class="subtle">Notes for this hosting account.</p>

            <div class="tip-item">
                <h3>Shortcuts</h3>
                <p>Items without a link show <strong>Soon</strong> until you set <code>route</code> or <code>url</code> in <code>config/host_panel.php</code>.</p>
            </div>
            <div class="tip-item">
                <h3>Web root</h3>
                <p>Public site files usually live under <code>public_html</code>.</p>
            </div>
            <div class="tip-item">
                <h3>Live site</h3>
                <p>Use <strong>Open Site</strong> for the domain; <strong>Open IP</strong> hits the server by IP only.</p>
            </div>
        </div>
    </div>
</div>
@endsection
