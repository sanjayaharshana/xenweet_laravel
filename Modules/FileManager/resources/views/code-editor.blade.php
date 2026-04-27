@extends('layouts.host')

@section('title', $hosting->domain)

@section('content')
@php
    $workspacePath = (string) $workspacePath;
    $backToFm = $workspacePath === ''
        ? route('hosts.files.index', $hosting)
        : route('hosts.files.index', ['hosting' => $hosting, 'path' => $workspacePath]);
@endphp
<div
    class="host-panel-scope fm-code-editor"
    id="fm-code-editor-root"
    data-workspace-path="{{ e($workspacePath) }}"
    data-entries-url="{{ e(route('hosts.files.entries', $hosting)) }}"
    data-edit-url="{{ e(route('hosts.files.edit', $hosting)) }}"
    data-update-url="{{ e(route('hosts.files.update', $hosting)) }}"
    data-csrf="{{ e(csrf_token()) }}"
    data-back-url="{{ e($backToFm) }}"
>
    <header class="fm-code-editor__topbar topbar">
        <p class="subtle fm-code-editor__file-meta" id="fm-code-editor-file-label" hidden></p>
    </header>

    <nav class="fm-menubar" id="fm-menubar" aria-label="Editor menu" role="menubar">
        <div class="fm-menubar__item" data-fm-menublock>
            <button type="button" class="fm-menubar__trigger" data-fm-menutrigger id="mb-trigger-file" aria-haspopup="true" aria-expanded="false" aria-controls="mb-panel-file">File</button>
            <div class="fm-menubar__panel" id="mb-panel-file" role="menu" hidden>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="file-save">Save <span class="fm-menubar__accel">Ctrl+S</span></button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="file-close">Close file</button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="file-refresh">Refresh</button>
                <hr class="fm-menubar__sep" />
                <a class="fm-menubar__opt fm-menubar__opt--link" role="menuitem" href="{{ $hosting->publicSiteUrl() }}" target="_blank" rel="noopener noreferrer">Open site</a>
                <a class="fm-menubar__opt fm-menubar__opt--link" role="menuitem" href="{{ $backToFm }}">Back to file manager</a>
            </div>
        </div>
        <div class="fm-menubar__item" data-fm-menublock>
            <button type="button" class="fm-menubar__trigger" data-fm-menutrigger id="mb-trigger-edit" aria-haspopup="true" aria-expanded="false" aria-controls="mb-panel-edit">Edit</button>
            <div class="fm-menubar__panel" id="mb-panel-edit" role="menu" hidden>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="edit-undo">Undo <span class="fm-menubar__accel">Ctrl+Z</span></button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="edit-redo">Redo <span class="fm-menubar__accel">Ctrl+Y</span></button>
                <hr class="fm-menubar__sep" />
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="edit-cut">Cut <span class="fm-menubar__accel">Ctrl+X</span></button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="edit-copy">Copy <span class="fm-menubar__accel">Ctrl+C</span></button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="edit-paste">Paste <span class="fm-menubar__accel">Ctrl+V</span></button>
                <hr class="fm-menubar__sep" />
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="edit-select-all">Select all <span class="fm-menubar__accel">Ctrl+A</span></button>
            </div>
        </div>
        <div class="fm-menubar__item" data-fm-menublock>
            <button type="button" class="fm-menubar__trigger" data-fm-menutrigger id="mb-trigger-sel" aria-haspopup="true" aria-expanded="false" aria-controls="mb-panel-sel">Selection</button>
            <div class="fm-menubar__panel" id="mb-panel-sel" role="menu" hidden>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="sel-find">Find <span class="fm-menubar__accel">Ctrl+F</span></button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="sel-replace">Replace <span class="fm-menubar__accel">Ctrl+H</span></button>
            </div>
        </div>
        <div class="fm-menubar__item" data-fm-menublock>
            <button type="button" class="fm-menubar__trigger" data-fm-menutrigger id="mb-trigger-view" aria-haspopup="true" aria-expanded="false" aria-controls="mb-panel-view">View</button>
            <div class="fm-menubar__panel" id="mb-panel-view" role="menu" hidden>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="view-wordwrap">Word wrap</button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="view-minimap">Minimap</button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="view-larger">Larger font</button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="view-smaller">Smaller font</button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="view-theme">Theme</button>
            </div>
        </div>
        <div class="fm-menubar__item" data-fm-menublock>
            <button type="button" class="fm-menubar__trigger" data-fm-menutrigger id="mb-trigger-go" aria-haspopup="true" aria-expanded="false" aria-controls="mb-panel-go">Go</button>
            <div class="fm-menubar__panel" id="mb-panel-go" role="menu" hidden>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="go-line">Go to line <span class="fm-menubar__accel">Ctrl+G</span></button>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="go-focus-sidebar">File list</button>
            </div>
        </div>
        <div class="fm-menubar__item" data-fm-menublock>
            <button type="button" class="fm-menubar__trigger" data-fm-menutrigger id="mb-trigger-help" aria-haspopup="true" aria-expanded="false" aria-controls="mb-panel-help">Help</button>
            <div class="fm-menubar__panel" id="mb-panel-help" role="menu" hidden>
                <button type="button" class="fm-menubar__opt" role="menuitem" data-cmd="help-keys">Shortcuts</button>
            </div>
        </div>
    </nav>

    <div class="fm-code-editor__layout">
        <aside class="fm-code-editor__sidebar server-card" aria-label="Folder">
            <p class="subtle fm-code-editor__browse-path" id="fm-code-editor-dir-label" hidden></p>
            <p class="subtle" id="fm-code-editor-entries-error" role="alert" hidden></p>
            <div class="fm-code-editor__nav">
                <button type="button" class="btn-secondary compact" id="fm-code-editor-up" disabled>Up</button>
            </div>
            <ul class="fm-code-editor__file-list" id="fm-code-editor-file-list"></ul>
        </aside>
        <div class="fm-code-editor__editor-wrap" aria-label="File content">
            <div id="fm-code-editor-container" class="fm-code-editor__monaco"></div>
        </div>
    </div>
</div>

<dialog class="file-manager-dialog file-manager-dialog--notice" id="fm-code-editor-help" aria-labelledby="fm-code-editor-help-title">
    <h3 class="file-manager-dialog__title" id="fm-code-editor-help-title">Shortcuts</h3>
    <ul class="fm-menubar-help-list">
        <li>Save: Ctrl+S / ⌘S</li>
        <li>Find / Replace: Ctrl+F / Ctrl+H</li>
        <li>Go to line: Ctrl+G</li>
        <li>Undo / Redo: Ctrl+Z / Ctrl+Y</li>
    </ul>
    <div class="file-manager-dialog__actions">
        <button type="button" class="btn-primary" id="fm-code-editor-help-close">OK</button>
    </div>
</dialog>

{{-- Editor: AMD loader from CDN; no app bundler. --}}
<script src="https://unpkg.com/monaco-editor@0.45.0/min/vs/loader.js"></script>
<script>
(function () {
    var root = document.getElementById('fm-code-editor-root');
    if (!root) {
        return;
    }

    var workspacePath = root.getAttribute('data-workspace-path') || '';
    var entriesBase = root.getAttribute('data-entries-url') || '';
    var editBase = root.getAttribute('data-edit-url') || '';
    var updateUrl = root.getAttribute('data-update-url') || '';
    var csrf = root.getAttribute('data-csrf') || '';

    var upBtn = document.getElementById('fm-code-editor-up');
    var fileList = document.getElementById('fm-code-editor-file-list');
    var dirLabel = document.getElementById('fm-code-editor-dir-label');
    var errEl = document.getElementById('fm-code-editor-entries-error');
    var fileLabel = document.getElementById('fm-code-editor-file-label');
    var monacoContainer = document.getElementById('fm-code-editor-container');

    var currentDir = workspacePath;
    var currentFile = '';
    var listMeta = { path: '', parent: null };

    var editor = null;
    var isDirty = false;

    function isPathUnderChild(parentRoot, p) {
        if (parentRoot === '') {
            return true;
        }
        if (p === parentRoot) {
            return true;
        }
        return p.indexOf(parentRoot + '/') === 0;
    }

    function urlWithQuery(base, params) {
        var u = new URL(base, window.location.origin);
        Object.keys(params).forEach(function (k) {
            u.searchParams.set(k, params[k]);
        });
        return u.toString();
    }

    function languageFromPath(p) {
        var i = p.lastIndexOf('.');
        var ext = i >= 0 ? p.slice(i + 1).toLowerCase() : '';
        var map = {
            js: 'javascript', mjs: 'javascript', cjs: 'javascript',
            jsx: 'javascript', ts: 'typescript', tsx: 'typescript',
            json: 'json', html: 'html', htm: 'html', blade: 'html',
            css: 'css', scss: 'scss', less: 'less', sass: 'scss',
            md: 'markdown', mdown: 'markdown', vue: 'html',
            php: 'php', phtml: 'php', env: 'plaintext',
            yml: 'yaml', yaml: 'yaml', xml: 'xml', svg: 'xml',
            sh: 'shell', bash: 'shell', zsh: 'shell',
            sql: 'sql', py: 'python', rb: 'ruby', rs: 'rust', go: 'go',
            c: 'cpp', h: 'c', hpp: 'cpp', cc: 'cpp', cpp: 'cpp',
            java: 'java', kt: 'kotlin', swift: 'swift', cs: 'csharp',
        };
        return map[ext] || 'plaintext';
    }

    function setDirty(v) {
        isDirty = v;
    }

    function closeFile() {
        if (!editor) {
            return;
        }
        if (isDirty && !window.confirm('Discard unsaved changes?')) {
            return;
        }
        currentFile = '';
        editor.setValue('');
        setDirty(false);
        if (fileLabel) {
            fileLabel.textContent = '';
            fileLabel.hidden = true;
        }
        if (editor.getModel()) {
            monaco.editor.setModelLanguage(editor.getModel(), 'plaintext');
        }
    }

    var EDITOR_CMD_TO_ACTION = {
        'edit-undo': 'undo',
        'edit-redo': 'redo',
        'edit-cut': 'editor.action.clipboardCutAction',
        'edit-copy': 'editor.action.clipboardCopyAction',
        'edit-paste': 'editor.action.clipboardPasteAction',
        'edit-select-all': 'editor.action.selectAll',
        'sel-find': 'actions.find',
        'sel-replace': 'editor.action.startFindReplaceAction',
        'go-line': 'editor.action.gotoLine',
    };

    function runEditorActionById(actionId) {
        if (!editor) {
            return false;
        }
        var a = editor.getAction(actionId);
        if (a) {
            if (typeof a.isSupported === 'function' && !a.isSupported()) {
                return false;
            }
            a.run();
            return true;
        }
        return false;
    }

    function setEditorTheme() {
        if (typeof monaco === 'undefined' || !monaco || !monaco.editor) {
            return;
        }
        var light = document.documentElement.getAttribute('data-theme') === 'light';
        monaco.editor.setTheme(light ? 'vs' : 'vs-dark');
    }

    if (window.require && window.require.config) {
        window.require.config({
            paths: { vs: 'https://unpkg.com/monaco-editor@0.45.0/min/vs' }
        });
    }

    new MutationObserver(setEditorTheme).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    function renderList(entries) {
        if (!fileList) {
            return;
        }
        fileList.textContent = '';
        (entries || []).forEach(function (e) {
            var li = document.createElement('li');
            if (e.is_dir) {
                var bd = document.createElement('button');
                bd.type = 'button';
                bd.className = 'fm-code-editor__item fm-code-editor__item--dir';
                var ic = document.createElement('i');
                ic.className = 'fa fa-folder';
                ic.setAttribute('aria-hidden', 'true');
                bd.appendChild(ic);
                bd.appendChild(document.createTextNode(' ' + (e.name || '')));
                bd.setAttribute('data-rel', e.relative);
                li.appendChild(bd);
            } else {
                var bf = document.createElement('button');
                var can = !!e.editable;
                bf.type = 'button';
                bf.className = 'fm-code-editor__item' + (can ? '' : ' fm-code-editor__item--disabled');
                bf.disabled = !can;
                var icf = document.createElement('i');
                icf.className = 'fa fa-file-o';
                icf.setAttribute('aria-hidden', 'true');
                bf.appendChild(icf);
                bf.appendChild(document.createTextNode(' ' + (e.name || '')));
                if (can) {
                    bf.setAttribute('data-file-rel', e.relative);
                }
                li.appendChild(bf);
            }
            fileList.appendChild(li);
        });

        fileList.querySelectorAll('button[data-rel]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                currentDir = btn.getAttribute('data-rel') || '';
                loadDir();
            });
        });
        fileList.querySelectorAll('button[data-file-rel]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var rel = btn.getAttribute('data-file-rel') || '';
                if (rel) {
                    openFile(rel);
                }
            });
        });
    }

    function updateNav(data) {
        if (errEl) {
            errEl.hidden = true;
            errEl.textContent = '';
        }
        listMeta.path = data.path || '';
        listMeta.parent = Object.prototype.hasOwnProperty.call(data, 'parentRelativePath') ? data.parentRelativePath : null;
        if (dirLabel) {
            var d = data.path || '';
            dirLabel.textContent = d === '' ? '/' : d;
            dirLabel.hidden = false;
        }
        if (upBtn) {
            upBtn.disabled = (data.path || '') === workspacePath;
        }
    }

    function loadDir() {
        if (errEl) {
            errEl.hidden = true;
        }
        var u = urlWithQuery(entriesBase, { path: currentDir, root: workspacePath });
        fetch(u, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (!res.data || !res.data.ok) {
                    var msg = (res.data && res.data.message) ? res.data.message : 'Could not list folder.';
                    if (errEl) {
                        errEl.textContent = msg;
                        errEl.hidden = false;
                    }
                    return;
                }
                currentDir = res.data.path != null ? res.data.path : currentDir;
                updateNav(res.data);
                renderList(res.data.entries);
            })
            .catch(function () {
                if (errEl) {
                    errEl.textContent = 'Could not list folder.';
                    errEl.hidden = false;
                }
            });
    }

    function openFile(rel) {
        if (!editor) {
            return;
        }
        if (isDirty && !window.confirm('Discard unsaved changes?')) {
            return;
        }
        var u = new URL(editBase, window.location.origin);
        u.searchParams.set('path', rel);
        fetch(u.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (!res.data || !res.data.ok) {
                    var msg = (res.data && res.data.message) ? res.data.message : 'Could not open file.';
                    if (fileLabel) {
                        fileLabel.textContent = msg;
                        fileLabel.hidden = false;
                    }
                    return;
                }
                currentFile = rel;
                if (fileLabel) {
                    fileLabel.textContent = rel;
                    fileLabel.hidden = false;
                }
                var text = res.data.content != null ? res.data.content : '';
                var lang = languageFromPath(rel);
                if (editor.getModel()) {
                    monaco.editor.setModelLanguage(editor.getModel(), lang);
                }
                editor.setValue(text);
                setDirty(false);
            })
            .catch(function () {
                if (fileLabel) {
                    fileLabel.textContent = 'Could not open file.';
                    fileLabel.hidden = false;
                }
            });
    }

    function save() {
        if (!currentFile || !editor) {
            return;
        }
        var body = new URLSearchParams({
            _token: csrf,
            path: currentFile,
            content: editor.getValue()
        });
        fetch(updateUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            },
            body: body
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (res.data && res.data.ok) {
                    setDirty(false);
                    if (fileLabel && currentFile) {
                        fileLabel.textContent = currentFile;
                    }
                    return;
                }
                var msg = (res.data && res.data.message) ? res.data.message : 'Save failed.';
                window.alert(msg);
            })
            .catch(function () {
                window.alert('Save failed.');
            });
    }

    function handleMenubarCmd(cmd) {
        if (cmd === 'file-save') {
            save();
            return;
        }
        if (cmd === 'file-close') {
            closeFile();
            return;
        }
        if (cmd === 'file-refresh') {
            loadDir();
            return;
        }
        if (EDITOR_CMD_TO_ACTION[cmd]) {
            runEditorActionById(EDITOR_CMD_TO_ACTION[cmd]);
            return;
        }
        if (cmd === 'view-wordwrap') {
            if (!editor) {
                return;
            }
            if (!runEditorActionById('editor.action.toggleWordWrap')) {
                var raw = editor.getRawOptions ? editor.getRawOptions() : {};
                var w = raw.wordWrap === 'on' ? 'off' : 'on';
                editor.updateOptions({ wordWrap: w });
            }
            return;
        }
        if (cmd === 'view-minimap') {
            if (!runEditorActionById('editor.action.toggleMinimap') && editor) {
                var r = editor.getRawOptions ? editor.getRawOptions() : {};
                var m = r.minimap || {};
                editor.updateOptions({ minimap: { enabled: !m.enabled } });
            }
            return;
        }
        if (cmd === 'view-larger') {
            if (!editor || !monaco || !monaco.editor) {
                return;
            }
            try {
                var fs = editor.getOption(monaco.editor.EditorOption.fontSize) || 14;
                editor.updateOptions({ fontSize: Math.min(28, fs + 1) });
            } catch (e3) {
                editor.updateOptions({ fontSize: 16 });
            }
            return;
        }
        if (cmd === 'view-smaller') {
            if (!editor || !monaco || !monaco.editor) {
                return;
            }
            try {
                var fs2 = editor.getOption(monaco.editor.EditorOption.fontSize) || 14;
                editor.updateOptions({ fontSize: Math.max(10, fs2 - 1) });
            } catch (e4) {
                editor.updateOptions({ fontSize: 12 });
            }
            return;
        }
        if (cmd === 'view-theme') {
            var tb = document.getElementById('theme-toggle');
            if (tb) {
                tb.click();
            }
            return;
        }
        if (cmd === 'go-focus-sidebar') {
            if (fileList) {
                fileList.setAttribute('tabindex', '-1');
                fileList.focus();
            }
            return;
        }
        if (cmd === 'help-keys') {
            var hd = document.getElementById('fm-code-editor-help');
            if (hd && typeof hd.showModal === 'function') {
                hd.showModal();
            } else {
                window.alert('Use the File menu to save.');
            }
        }
    }

    function initMenubar() {
        var bar = document.getElementById('fm-menubar');
        if (!bar) {
            return;
        }
        function closeAllMenus() {
            bar.querySelectorAll('[data-fm-menutrigger]').forEach(function (t) {
                t.setAttribute('aria-expanded', 'false');
                var pid = t.getAttribute('aria-controls');
                var p = pid ? document.getElementById(pid) : null;
                if (p) {
                    p.hidden = true;
                }
            });
        }

        function openMenuBlock(block) {
            closeAllMenus();
            var tr = block.querySelector('[data-fm-menutrigger]');
            var panel = block.querySelector('.fm-menubar__panel');
            if (tr && panel) {
                tr.setAttribute('aria-expanded', 'true');
                panel.hidden = false;
            }
        }

        bar.querySelectorAll('[data-fm-menutrigger]').forEach(function (tr) {
            tr.addEventListener('click', function (e) {
                e.stopPropagation();
                var block = tr.closest('[data-fm-menublock]');
                if (!block) {
                    return;
                }
                var expanded = tr.getAttribute('aria-expanded') === 'true';
                if (expanded) {
                    closeAllMenus();
                } else {
                    openMenuBlock(block);
                }
            });
        });

        document.addEventListener('click', function (e) {
            if (bar.contains(e.target)) {
                return;
            }
            closeAllMenus();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAllMenus();
            }
        });

        bar.querySelectorAll('[data-cmd]').forEach(function (opt) {
            opt.addEventListener('click', function (e) {
                var c = opt.getAttribute('data-cmd');
                if (!c) {
                    return;
                }
                e.preventDefault();
                handleMenubarCmd(c);
                closeAllMenus();
            });
        });

        bar.querySelectorAll('a.fm-menubar__opt--link').forEach(function (a) {
            a.addEventListener('click', function () {
                closeAllMenus();
            });
        });

        var helpClose = document.getElementById('fm-code-editor-help-close');
        var helpDlg = document.getElementById('fm-code-editor-help');
        if (helpClose && helpDlg) {
            helpClose.addEventListener('click', function () {
                helpDlg.close();
            });
            helpDlg.addEventListener('click', function (ev) {
                if (ev.target === helpDlg) {
                    helpDlg.close();
                }
            });
        }
    }

    if (upBtn) {
        upBtn.addEventListener('click', function () {
            if (listMeta.path === workspacePath) {
                return;
            }
            var par = listMeta.parent;
            if (par === null) {
                currentDir = workspacePath;
                loadDir();
                return;
            }
            if (workspacePath !== '' && (par === '' || !isPathUnderChild(workspacePath, par))) {
                currentDir = workspacePath;
            } else {
                currentDir = par;
            }
            loadDir();
        });
    }

    if (window.require) {
        window.require(['vs/editor/editor.main'], function () {
            setEditorTheme();
            editor = monaco.editor.create(monacoContainer, {
                value: '',
                language: 'plaintext',
                fontSize: 14,
                minimap: { enabled: true },
                scrollBeyondLastLine: false,
                automaticLayout: true,
                wordWrap: 'on',
                tabSize: 4
            });
            editor.onDidChangeModelContent(function () {
                if (currentFile) {
                    setDirty(true);
                }
            });
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                    e.preventDefault();
                    if (currentFile) {
                        save();
                    }
                }
            }, true);
            initMenubar();
            loadDir();
        });
    } else {
        if (fileLabel) {
            fileLabel.textContent = 'Editor could not load.';
            fileLabel.hidden = false;
        }
    }
})();
</script>
@endsection
