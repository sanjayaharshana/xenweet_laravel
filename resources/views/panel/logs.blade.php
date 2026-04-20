@extends('layouts.panel')

@section('title', 'Logs - Xenweet Panel')

@section('content')
    <header class="topbar">
        <div>
            <p class="eyebrow">System</p>
            <h1>Log viewer</h1>
            <p class="subtle">Read-only tail of files in <code>storage/logs</code> (last {{ number_format($maxBytes) }} bytes max per file).</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary compact" href="{{ route('panel.logs', request()->query()) }}">Refresh</a>
        </div>
    </header>

    @if ($logFiles !== [])
        <div class="log-viewer-toolbar server-card">
            <label for="log-file">Log file</label>
            <form method="GET" action="{{ route('panel.logs') }}" class="log-file-picker">
                <select id="log-file" name="file" onchange="this.form.submit()">
                    @foreach ($logFiles as $name)
                        <option value="{{ $name }}" @selected($currentFile === $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </form>
            @if ($truncated)
                <span class="log-truncation-note">Large file — showing the end only.</span>
            @endif
        </div>
    @endif

    <section class="server-card log-viewer-card">
        <pre class="log-viewer" id="log-content" spellcheck="false">{{ $content }}</pre>
    </section>
@endsection
