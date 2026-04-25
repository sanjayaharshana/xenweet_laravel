@extends('layouts.host')

@section('title', 'Edit file - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope file-manager-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Text editor</p>
            <h1>{{ $relativePath }}</h1>
            <p class="subtle">UTF-8 text only. Allowed types match the file manager configuration.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.files.index', array_filter(['hosting' => $hosting, 'path' => $parentPath])) }}">Back to file list</a>
        </div>
    </header>

    @if (session('success'))
        <div class="file-manager-flash file-manager-flash--success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->has('action'))
        <div class="file-manager-flash file-manager-flash--error" role="alert">{{ $errors->first('action') }}</div>
    @endif
    @if ($errors->has('content'))
        <div class="file-manager-flash file-manager-flash--error" role="alert">{{ $errors->first('content') }}</div>
    @endif

    <div class="server-card file-manager-editor-card">
        <form method="post" action="{{ route('hosts.files.update', $hosting) }}" class="file-manager-editor-form">
            @csrf
            <input type="hidden" name="path" value="{{ $relativePath }}">
            <label class="file-manager-editor-label" for="file-content">Content</label>
            <textarea
                id="file-content"
                name="content"
                class="file-manager-editor-textarea"
                rows="24"
                spellcheck="false"
            >{{ old('content', $content) }}</textarea>
            <div class="file-manager-editor-actions">
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
