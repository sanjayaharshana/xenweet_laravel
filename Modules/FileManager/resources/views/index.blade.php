@extends('layouts.panel')

@section('title', 'File Manager - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope file-manager-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">File Manager</p>
            <h1>{{ $hosting->domain }}</h1>
            <p class="subtle">Browse and manage files for this hosting account.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
            <a class="btn-secondary compact" href="{{ $hosting->publicSiteUrl() }}" target="_blank" rel="noopener noreferrer">Open Site</a>
        </div>
    </header>

    <div class="file-manager-placeholder server-card">
        <p class="file-manager-placeholder__icon" aria-hidden="true"><i class="fa fa-folder-open"></i></p>
        <h3 class="file-manager-placeholder__title">File browser</h3>
        <p class="subtle">Listing, upload, and edit will connect to the host over SSH/SFTP or your storage API. This page is the entry point from the host panel.</p>
    </div>
</div>
@endsection
