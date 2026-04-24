@extends('layouts.panel')

@section('title', 'Terminal - Xenweet')

@section('content')
<div class="host-panel-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Security</p>
            <h1>Terminal</h1>
            <p class="subtle">Web terminal powered by xterm.js for this hosting account.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    <p class="ssltls-workflow-eyebrow" id="ssh-tabs-h">Host panel tabs</p>
    <nav class="managedb-tabs ssltls-tool-tabs" aria-label="Host panel tabs" aria-describedby="ssh-tabs-h">
        <a href="{{ route('hosts.panel', $hosting) }}" class="managedb-tab">
            <i class="fa fa-th-large" aria-hidden="true"></i> Overview
        </a>
        <span class="managedb-tab is-active" aria-current="page">
            <i class="fa fa-terminal" aria-hidden="true"></i> Terminal
        </span>
        <a href="{{ route('hosts.ssh-access', $hosting) }}" class="managedb-tab">
            <i class="fa fa-lock" aria-hidden="true"></i> SSH Access
        </a>
    </nav>

    <section class="server-card" aria-labelledby="terminal-card-h">
        <h2 id="terminal-card-h">Terminal session</h2>
        <p class="subtle" style="margin:0.2rem 0 0.6rem;">Working directory: <code>{{ $terminalCwd }}</code></p>
        <div id="xterm-shell" style="height: 430px; width: 100%; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; background: #0b1021;"></div>
    </section>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm/css/xterm.css">
<script src="https://cdn.jsdelivr.net/npm/xterm/lib/xterm.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit/lib/xterm-addon-fit.js"></script>
<script>
(function () {
    var shellEl = document.getElementById('xterm-shell');
    if (!shellEl || !window.Terminal) { return; }

    var term = new Terminal({
        cursorBlink: true,
        fontSize: 13,
        theme: {
            background: '#0b1021',
            foreground: '#dbe7ff',
            cursor: '#9cc3ff'
        }
    });
    var fitAddon = new FitAddon.FitAddon();
    term.loadAddon(fitAddon);
    term.open(shellEl);
    fitAddon.fit();

    var cwd = @json($terminalCwd);
    var runUrl = @json($terminalRunUrl);
    var csrf = @json(csrf_token());
    var busy = false;
    var input = '';

    function prompt() {
        term.write('\r\n$ ');
    }

    function printLine(line) {
        term.write((line || '').replace(/\r?\n/g, '\r\n'));
    }

    async function execute(command) {
        busy = true;
        try {
            var resp = await fetch(runUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ command: command })
            });

            var data;
            try {
                data = await resp.json();
            } catch (jsonErr) {
                data = { output: '', exit_code: 1 };
            }
            if (data.output) {
                term.write('\r\n');
                printLine(String(data.output));
            }
            if (!resp.ok) {
                term.write('\r\n[error] command failed (' + resp.status + ')');
            }
            term.write('\r\n[exit ' + String(typeof data.exit_code === 'number' ? data.exit_code : 1) + ']');
        } catch (e) {
            term.write('\r\n[error] request failed: ' + String(e));
        } finally {
            busy = false;
            input = '';
            prompt();
        }
    }

    term.writeln('Xenweet Terminal (xterm.js)');
    term.writeln('Connected to: ' + cwd);
    term.writeln('Type a command and press Enter.');
    prompt();

    term.onData(function (data) {
        if (busy) {
            return;
        }

        if (data === '\r') {
            var cmd = input.trim();
            if (cmd === '') {
                prompt();
                return;
            }
            execute(cmd);
            return;
        }

        if (data === '\u007F') {
            if (input.length > 0) {
                input = input.slice(0, -1);
                term.write('\b \b');
            }
            return;
        }

        if (data >= String.fromCharCode(0x20) && data <= String.fromCharCode(0x7E)) {
            input += data;
            term.write(data);
        }
    });

    window.addEventListener('resize', function () { fitAddon.fit(); });
})();
</script>
@endsection
