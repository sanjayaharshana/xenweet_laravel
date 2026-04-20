@extends('layouts.panel')

@section('title', 'Host Panel - Xenweet')

@section('content')
    <header class="topbar">
        <div>
            <p class="eyebrow">Host Panel</p>
            <h1>{{ $hosting->domain }}</h1>
            <p class="subtle">File Manager for this hosting account.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('panel') }}">Back to Hosting List</a>
            <a class="btn-primary compact" href="{{ route('hosts.panel', ['hosting' => $hosting, 'path' => '']) }}">Root Folder</a>
            <a class="btn-secondary compact" href="{{ $hosting->publicSiteUrl() }}" target="_blank" rel="noopener noreferrer">Open Site</a>
            <a class="btn-secondary compact subtle-link" href="http://{{ $hosting->server_ip }}" target="_blank" rel="noopener noreferrer" title="Direct IP">Open IP</a>
        </div>
    </header>

    <section class="server-card">
        <div class="meta">
            <div><span>Domain</span><strong>{{ $hosting->domain }}</strong></div>
            <div><span>Server IP</span><strong>{{ $hosting->server_ip }}</strong></div>
            <div><span>Plan</span><strong>{{ $hosting->plan }}</strong></div>
            <div><span>Web Root</span><strong>{{ $hosting->web_root_path ?: 'Not assigned yet' }}</strong></div>
            <div><span>Current Path</span><strong>/{{ $relativePath }}</strong></div>
        </div>
    </section>

    <section class="server-card" style="margin-top: 1rem;">
        <div class="file-toolbar">
            <h2>File Manager</h2>
            @if ($relativePath !== '')
                <a class="btn-secondary compact-btn" href="{{ route('hosts.panel', ['hosting' => $hosting, 'path' => $parentPath]) }}">Go Up</a>
            @endif
        </div>

        <div class="file-table">
            <div class="file-row file-row-head">
                <span>Name</span>
                <span>Type</span>
                <span>Size</span>
                <span>Modified</span>
            </div>

            @forelse ($items as $item)
                <div class="file-row">
                    <span>
                        @if ($item['type'] === 'directory')
                            <a href="{{ route('hosts.panel', ['hosting' => $hosting, 'path' => $item['path']]) }}">📁 {{ $item['name'] }}</a>
                        @else
                            📄 {{ $item['name'] }}
                        @endif
                    </span>
                    <span>{{ ucfirst($item['type']) }}</span>
                    <span>{{ $item['size'] }}</span>
                    <span>{{ $item['modified'] }}</span>
                </div>
            @empty
                <div class="file-row">
                    <span>No files yet</span>
                    <span>--</span>
                    <span>--</span>
                    <span>--</span>
                </div>
            @endforelse
        </div>
    </section>
@endsection
