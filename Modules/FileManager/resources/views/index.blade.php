@extends('layouts.panel')

@section('title', 'File Manager - ' . $hosting->domain)

@section('content')
<div class="host-panel-scope file-manager-scope">
    @if (! $listing['ok'])
        <div class="file-manager-actions-toolbar" aria-label="Navigation">
            <div class="file-manager-actions-toolbar__buttons">
                <a class="file-manager-icon-btn file-manager-icon-btn--link" href="{{ route('hosts.panel', $hosting) }}" title="Back to Host Panel">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Host Panel</span>
                </a>
                <a class="file-manager-icon-btn file-manager-icon-btn--link" href="{{ $hosting->publicSiteUrl() }}" target="_blank" rel="noopener noreferrer" title="Open site in new tab">
                    <i class="fa fa-globe" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Open Site</span>
                </a>
            </div>
        </div>
        <div class="file-manager-alert server-card" role="alert">
            <p class="file-manager-alert__text">{{ $listing['error'] }}</p>
        </div>
    @else
        <div
            class="file-manager-panel server-card"
            data-rename-url="{{ route('hosts.files.rename', $hosting) }}"
            data-open-url="{{ route('hosts.files.open', $hosting) }}"
            data-edit-url="{{ route('hosts.files.edit', $hosting) }}"
            data-update-url="{{ route('hosts.files.update', $hosting) }}"
        >
            @if (session('success'))
                <div class="file-manager-flash file-manager-flash--success" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->has('action'))
                <div class="file-manager-flash file-manager-flash--error" role="alert">{{ $errors->first('action') }}</div>
            @endif

            <div class="file-manager-actions-toolbar" aria-label="File manager tools">
                <div class="file-manager-actions-toolbar__buttons">
                    <a class="file-manager-icon-btn file-manager-icon-btn--link" href="{{ route('hosts.panel', $hosting) }}" title="Back to Host Panel">
                        <i class="fa fa-arrow-left" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Host Panel</span>
                    </a>
                    <a class="file-manager-icon-btn file-manager-icon-btn--link" href="{{ $hosting->publicSiteUrl() }}" target="_blank" rel="noopener noreferrer" title="Open site in new tab">
                        <i class="fa fa-globe" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Open Site</span>
                    </a>
                    <span class="file-manager-toolbar-sep" aria-hidden="true"></span>
                    <button type="button" class="file-manager-icon-btn" data-open-dialog="fm-dialog-mkdir" title="Create folder">
                        <i class="fa fa-folder-o" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Folder</span>
                    </button>
                    <button type="button" class="file-manager-icon-btn" data-open-dialog="fm-dialog-file" title="Create file">
                        <i class="fa fa-file-o" aria-hidden="true"></i><span class="file-manager-icon-btn__label">File</span>
                    </button>
                    <button type="button" class="file-manager-icon-btn" data-open-dialog="fm-dialog-upload" title="Upload">
                        <i class="fa fa-cloud-upload" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Upload</span>
                    </button>
                    <button type="button" class="file-manager-icon-btn" data-open-dialog="fm-dialog-delete" data-requires-selection="1" title="Delete selected">
                        <i class="fa fa-trash" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Delete</span>
                    </button>
                    <button type="button" class="file-manager-icon-btn" data-open-dialog="fm-dialog-move" data-requires-selection="1" title="Move selected">
                        <i class="fa fa-arrows" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Move</span>
                    </button>
                    <a class="file-manager-icon-btn file-manager-icon-btn--link" href="{{ route('hosts.files.index', array_filter(['hosting' => $hosting, 'path' => $listing['relativePath']])) }}" title="Refresh">
                        <i class="fa fa-refresh" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Refresh</span>
                    </a>
                    <button type="button" class="file-manager-icon-btn" data-open-dialog="fm-dialog-download" title="Download">
                        <i class="fa fa-download" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Download</span>
                    </button>
                    <button type="button" class="file-manager-icon-btn" data-open-dialog="fm-dialog-copy" title="Copy">
                        <i class="fa fa-files-o" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Copy</span>
                    </button>
                    <button type="button" class="file-manager-icon-btn" data-open-dialog="fm-dialog-rename" title="Rename">
                        <i class="fa fa-pencil" aria-hidden="true"></i><span class="file-manager-icon-btn__label">Rename</span>
                    </button>
                </div>
            </div>

            <dialog id="fm-dialog-mkdir" class="file-manager-dialog">
                <form method="post" action="{{ route('hosts.files.mkdir', $hosting) }}">
                    @csrf
                    <input type="hidden" name="path" value="{{ $listing['relativePath'] }}">
                    <h3 class="file-manager-dialog__title"><i class="fa fa-folder-o" aria-hidden="true"></i> New folder</h3>
                    <p class="file-manager-dialog__hint subtle">Create a folder in the current directory.</p>
                    <label class="file-manager-dialog__field">
                        <span>Folder name</span>
                        <input type="text" name="name" maxlength="255" autocomplete="off" required>
                    </label>
                    <div class="file-manager-dialog__actions">
                        <button type="button" class="btn-secondary" data-close-dialog>Cancel</button>
                        <button type="submit" class="btn-primary">Create</button>
                    </div>
                </form>
            </dialog>

            <dialog id="fm-dialog-file" class="file-manager-dialog">
                <form method="post" action="{{ route('hosts.files.touch', $hosting) }}">
                    @csrf
                    <input type="hidden" name="path" value="{{ $listing['relativePath'] }}">
                    <h3 class="file-manager-dialog__title"><i class="fa fa-file-o" aria-hidden="true"></i> New file</h3>
                    <p class="file-manager-dialog__hint subtle">Create an empty file in the current directory.</p>
                    <label class="file-manager-dialog__field">
                        <span>File name</span>
                        <input type="text" name="name" maxlength="255" autocomplete="off" required>
                    </label>
                    <div class="file-manager-dialog__actions">
                        <button type="button" class="btn-secondary" data-close-dialog>Cancel</button>
                        <button type="submit" class="btn-primary">Create</button>
                    </div>
                </form>
            </dialog>

            <dialog id="fm-dialog-upload" class="file-manager-dialog">
                <form method="post" action="{{ route('hosts.files.upload', $hosting) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="path" value="{{ $listing['relativePath'] }}">
                    <h3 class="file-manager-dialog__title"><i class="fa fa-cloud-upload" aria-hidden="true"></i> Upload file</h3>
                    <p class="file-manager-dialog__hint subtle">Upload into the current folder.</p>
                    <label class="file-manager-dialog__field">
                        <span>Choose file</span>
                        <input type="file" name="file" required>
                    </label>
                    <div class="file-manager-dialog__actions">
                        <button type="button" class="btn-secondary" data-close-dialog>Cancel</button>
                        <button type="submit" class="btn-primary">Upload</button>
                    </div>
                </form>
            </dialog>

            <dialog id="fm-dialog-delete" class="file-manager-dialog file-manager-dialog--notice">
                <h3 class="file-manager-dialog__title"><i class="fa fa-trash" aria-hidden="true"></i> Delete</h3>
                <p class="file-manager-dialog__hint">Permanently delete the selected files or folders? This cannot be undone.</p>
                <div class="file-manager-dialog__actions">
                    <button type="button" class="btn-secondary" data-close-dialog>Cancel</button>
                    <button type="submit" class="btn-primary file-manager-dialog__btn-danger" form="file-manager-bulk" formaction="{{ route('hosts.files.destroy', $hosting) }}">Delete</button>
                </div>
            </dialog>

            <dialog id="fm-dialog-move" class="file-manager-dialog">
                <form method="post" action="{{ route('hosts.files.move', $hosting) }}" id="fm-form-move">
                    @csrf
                    <input type="hidden" name="path" value="{{ $listing['relativePath'] }}">
                    <h3 class="file-manager-dialog__title"><i class="fa fa-arrows" aria-hidden="true"></i> Move</h3>
                    <p class="file-manager-dialog__hint subtle">Move selected items to another folder under this host (relative path from host root).</p>
                    <label class="file-manager-dialog__field">
                        <span>Destination folder</span>
                        <input type="text" name="destination" maxlength="4096" autocomplete="off" placeholder="e.g. public_html or backups/sub" required>
                    </label>
                    <p class="file-manager-dialog__hint subtle">Selected items are submitted with this request.</p>
                    <div class="file-manager-dialog__actions">
                        <button type="button" class="btn-secondary" data-close-dialog>Cancel</button>
                        <button type="submit" class="btn-primary">Move</button>
                    </div>
                </form>
            </dialog>

            <dialog id="fm-dialog-download" class="file-manager-dialog file-manager-dialog--notice">
                <h3 class="file-manager-dialog__title"><i class="fa fa-download" aria-hidden="true"></i> Download</h3>
                <p class="file-manager-dialog__hint">This action is not available yet.</p>
                <div class="file-manager-dialog__actions">
                    <button type="button" class="btn-primary" data-close-dialog>OK</button>
                </div>
            </dialog>

            <dialog id="fm-dialog-copy" class="file-manager-dialog file-manager-dialog--notice">
                <h3 class="file-manager-dialog__title"><i class="fa fa-files-o" aria-hidden="true"></i> Copy</h3>
                <p class="file-manager-dialog__hint">This action is not available yet.</p>
                <div class="file-manager-dialog__actions">
                    <button type="button" class="btn-primary" data-close-dialog>OK</button>
                </div>
            </dialog>

            <dialog id="fm-dialog-rename" class="file-manager-dialog file-manager-dialog--notice">
                <h3 class="file-manager-dialog__title"><i class="fa fa-pencil" aria-hidden="true"></i> Rename</h3>
                <p class="file-manager-dialog__hint">This action is not available yet.</p>
                <div class="file-manager-dialog__actions">
                    <button type="button" class="btn-primary" data-close-dialog>OK</button>
                </div>
            </dialog>

            <dialog id="fm-dialog-edit" class="file-manager-dialog file-manager-dialog--edit">
                <div class="file-manager-dialog--edit__inner">
                    <h3 class="file-manager-dialog__title"><i class="fa fa-pencil" aria-hidden="true"></i> Edit file</h3>
                    <p class="file-manager-dialog__path subtle" id="fm-edit-path-label"></p>
                    <textarea id="fm-edit-content" class="file-manager-dialog--edit__textarea" spellcheck="false" rows="16" placeholder="Loading…"></textarea>
                    <p class="file-manager-dialog--edit__error" id="fm-edit-error" hidden role="alert"></p>
                    <div class="file-manager-dialog__actions">
                        <button type="button" class="btn-secondary" data-close-dialog>Cancel</button>
                        <button type="button" class="btn-primary" id="fm-edit-save">Save</button>
                    </div>
                </div>
            </dialog>

            <form id="file-manager-bulk" method="post" action="{{ route('hosts.files.destroy', $hosting) }}" hidden aria-hidden="true">
                @csrf
                <input type="hidden" name="path" value="{{ $listing['relativePath'] }}">
            </form>

            <form id="fm-form-context-duplicate" method="post" action="{{ route('hosts.files.duplicate', $hosting) }}" hidden aria-hidden="true">
                @csrf
                <input type="hidden" name="path" value="{{ $listing['relativePath'] }}">
                <input type="hidden" name="from" id="fm-context-duplicate-from" value="">
            </form>

            <form id="fm-form-context-delete" method="post" action="{{ route('hosts.files.destroy', $hosting) }}" hidden aria-hidden="true">
                @csrf
                <input type="hidden" name="path" value="{{ $listing['relativePath'] }}">
                <input type="hidden" name="items[]" id="fm-context-delete-item" value="">
            </form>

            <div id="fm-context-menu" class="fm-context-menu" role="menu" hidden>
                <button type="button" class="fm-context-menu__item" role="menuitem" data-action="copy">Copy</button>
                <button type="button" class="fm-context-menu__item fm-context-menu__item--danger" role="menuitem" data-action="delete">Delete</button>
                <button type="button" class="fm-context-menu__item" role="menuitem" data-action="edit">Edit</button>
                <button type="button" class="fm-context-menu__item" role="menuitem" data-action="open">Open file</button>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    (function () {
                        const bulkId = 'file-manager-bulk';
                        function selectedCount() {
                            return document.querySelectorAll('input[form="' + bulkId + '"][name="items[]"]:checked').length;
                        }
                        document.querySelectorAll('[data-open-dialog]').forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                if (btn.getAttribute('data-requires-selection') && selectedCount() === 0) {
                                    window.alert('Select one or more items in the list first.');
                                    return;
                                }
                                var id = btn.getAttribute('data-open-dialog');
                                var el = id ? document.getElementById(id) : null;
                                if (el && typeof el.showModal === 'function') el.showModal();
                            });
                        });
                        document.querySelectorAll('[data-close-dialog]').forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                var d = btn.closest('dialog');
                                if (d) d.close();
                            });
                        });
                        document.querySelectorAll('dialog.file-manager-dialog').forEach(function (dialog) {
                            dialog.addEventListener('click', function (e) {
                                if (e.target === dialog) dialog.close();
                            });
                        });
                        var moveForm = document.getElementById('fm-form-move');
                        if (moveForm) {
                            moveForm.addEventListener('submit', function (e) {
                                var dest = moveForm.querySelector('input[name="destination"]');
                                if (dest && !dest.value.trim()) {
                                    e.preventDefault();
                                    window.alert('Enter a destination folder.');
                                    return;
                                }
                                moveForm.querySelectorAll('input.fm-move-sync').forEach(function (n) { n.remove(); });
                                document.querySelectorAll('input[form="' + bulkId + '"][name="items[]"]:checked').forEach(function (cb) {
                                    var hidden = document.createElement('input');
                                    hidden.type = 'hidden';
                                    hidden.name = 'items[]';
                                    hidden.value = cb.value;
                                    hidden.className = 'fm-move-sync';
                                    moveForm.appendChild(hidden);
                                });
                            });
                        }

                        var panel = document.querySelector('.file-manager-panel[data-rename-url]');
                        var renameUrl = panel ? panel.getAttribute('data-rename-url') : '';
                        function csrfToken() {
                            var t = document.querySelector('#file-manager-bulk input[name="_token"]');
                            return t ? t.value : '';
                        }

                        var fmMenu = document.getElementById('fm-context-menu');
                        var fmOpenBase = panel ? panel.getAttribute('data-open-url') : '';
                        var fmEditBase = panel ? panel.getAttribute('data-edit-url') : '';
                        var fmUpdateUrl = panel ? panel.getAttribute('data-update-url') : '';
                        var fmDialogEdit = document.getElementById('fm-dialog-edit');
                        var fmEditContent = document.getElementById('fm-edit-content');
                        var fmEditPathLabel = document.getElementById('fm-edit-path-label');
                        var fmEditError = document.getElementById('fm-edit-error');
                        var fmEditSave = document.getElementById('fm-edit-save');
                        var fmDupForm = document.getElementById('fm-form-context-duplicate');
                        var fmDelForm = document.getElementById('fm-form-context-delete');
                        var fmDupFrom = document.getElementById('fm-context-duplicate-from');
                        var fmDelItem = document.getElementById('fm-context-delete-item');
                        var ctxState = { relative: '', editable: false };
                        var ctxHighlightRow = null;

                        function clearCtxRowHighlight() {
                            if (ctxHighlightRow) {
                                ctxHighlightRow.classList.remove('file-row--menu-open');
                                ctxHighlightRow = null;
                            }
                        }

                        function fmUrl(base, relative) {
                            if (!base) {
                                return '';
                            }
                            var q = base.indexOf('?') >= 0 ? '&' : '?';
                            return base + q + 'path=' + encodeURIComponent(relative);
                        }

                        function openEditModal(rel) {
                            if (!fmDialogEdit || !fmEditContent) {
                                return;
                            }
                            fmEditError.hidden = true;
                            fmEditError.textContent = '';
                            fmEditPathLabel.textContent = rel;
                            fmDialogEdit.setAttribute('data-edit-path', rel);
                            fmEditContent.value = '';
                            fmEditContent.placeholder = 'Loading…';
                            fmEditContent.disabled = true;
                            if (fmEditSave) {
                                fmEditSave.disabled = true;
                            }
                            fmDialogEdit.showModal();
                            fetch(fmUrl(fmEditBase, rel), {
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }).then(function (r) {
                                return r.json().then(function (d) {
                                    return { ok: r.ok, data: d };
                                });
                            }).then(function (res) {
                                fmEditContent.disabled = false;
                                if (fmEditSave) {
                                    fmEditSave.disabled = false;
                                }
                                if (!res.ok || !res.data.ok) {
                                    var msg = 'Could not load file.';
                                    if (res.data && res.data.message) {
                                        msg = res.data.message;
                                    }
                                    fmEditError.textContent = msg;
                                    fmEditError.hidden = false;
                                    return;
                                }
                                fmEditContent.value = res.data.content != null ? res.data.content : '';
                                fmEditContent.placeholder = '';
                            }).catch(function () {
                                fmEditContent.disabled = false;
                                if (fmEditSave) {
                                    fmEditSave.disabled = false;
                                }
                                fmEditError.textContent = 'Could not load file.';
                                fmEditError.hidden = false;
                            });
                        }

                        if (fmDialogEdit && fmEditContent) {
                            fmDialogEdit.addEventListener('close', function () {
                                fmEditContent.value = '';
                                fmEditPathLabel.textContent = '';
                                fmDialogEdit.removeAttribute('data-edit-path');
                                fmEditError.hidden = true;
                            });
                        }

                        if (fmEditSave && fmUpdateUrl && fmDialogEdit) {
                            fmEditSave.addEventListener('click', function () {
                                var rel = fmDialogEdit.getAttribute('data-edit-path') || '';
                                if (!rel) {
                                    return;
                                }
                                fmEditSave.disabled = true;
                                fmEditError.hidden = true;
                                fetch(fmUpdateUrl, {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': csrfToken()
                                    },
                                    body: new URLSearchParams({
                                        _token: csrfToken(),
                                        path: rel,
                                        content: fmEditContent.value
                                    })
                                }).then(function (r) {
                                    return r.json().then(function (d) {
                                        return { ok: r.ok, data: d, status: r.status };
                                    });
                                }).then(function (res) {
                                    fmEditSave.disabled = false;
                                    if (res.data && res.data.ok) {
                                        fmDialogEdit.close();
                                        return;
                                    }
                                    var msg = 'Save failed.';
                                    if (res.data && res.data.message) {
                                        msg = res.data.message;
                                    }
                                    if (res.data && res.data.errors && res.data.errors.content) {
                                        msg = res.data.errors.content[0];
                                    }
                                    fmEditError.textContent = msg;
                                    fmEditError.hidden = false;
                                }).catch(function () {
                                    fmEditSave.disabled = false;
                                    fmEditError.textContent = 'Save failed.';
                                    fmEditError.hidden = false;
                                });
                            });
                        }

                        function hideFmMenu() {
                            clearCtxRowHighlight();
                            if (fmMenu) {
                                fmMenu.hidden = true;
                                fmMenu.style.position = '';
                                fmMenu.style.zIndex = '';
                                fmMenu.style.left = '';
                                fmMenu.style.top = '';
                                fmMenu.style.visibility = '';
                            }
                        }

                        function positionFmMenu(x, y) {
                            if (!fmMenu) {
                                return;
                            }
                            /* Detach from overflow:hidden ancestors so fixed + size work reliably */
                            if (fmMenu.parentNode !== document.body) {
                                document.body.appendChild(fmMenu);
                            }
                            fmMenu.hidden = false;
                            fmMenu.style.position = 'fixed';
                            fmMenu.style.zIndex = '10050';
                            /* While display:none, size is 0 — measure off-screen then place at cursor */
                            fmMenu.style.visibility = 'hidden';
                            fmMenu.style.left = '-10000px';
                            fmMenu.style.top = '0';
                            void fmMenu.offsetWidth;
                            var w = fmMenu.offsetWidth;
                            var h = fmMenu.offsetHeight;
                            var pad = 8;
                            var vw = window.innerWidth;
                            var vh = window.innerHeight;
                            var left = x;
                            var top = y;
                            if (w > 0 && h > 0) {
                                if (left + w + pad > vw) {
                                    left = Math.max(pad, vw - w - pad);
                                }
                                if (top + h + pad > vh) {
                                    top = Math.max(pad, vh - h - pad);
                                }
                            }
                            fmMenu.style.left = left + 'px';
                            fmMenu.style.top = top + 'px';
                            fmMenu.style.visibility = 'visible';
                        }

                        function setEditDisabled(disabled) {
                            var btn = fmMenu ? fmMenu.querySelector('[data-action="edit"]') : null;
                            if (!btn) {
                                return;
                            }
                            btn.disabled = disabled;
                            btn.classList.toggle('fm-context-menu__item--disabled', disabled);
                            btn.setAttribute('aria-disabled', disabled ? 'true' : 'false');
                        }

                        document.querySelectorAll('.file-row--file').forEach(function (row) {
                            row.addEventListener('contextmenu', function (e) {
                                e.preventDefault();
                                var rel = row.getAttribute('data-file-relative');
                                if (!rel || !fmMenu) {
                                    return;
                                }
                                ctxState.relative = rel;
                                ctxState.editable = row.getAttribute('data-file-editable') === '1';
                                clearCtxRowHighlight();
                                ctxHighlightRow = row;
                                row.classList.add('file-row--menu-open');
                                setEditDisabled(!ctxState.editable);
                                positionFmMenu(e.clientX, e.clientY);
                            });
                        });

                        document.addEventListener('click', function (e) {
                            if (fmMenu && !fmMenu.hidden && !fmMenu.contains(e.target)) {
                                hideFmMenu();
                            }
                        });

                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape') {
                                hideFmMenu();
                            }
                        });

                        window.addEventListener('scroll', hideFmMenu, true);

                        if (fmMenu) {
                            fmMenu.querySelectorAll('[data-action]').forEach(function (btn) {
                                btn.addEventListener('click', function () {
                                    var action = btn.getAttribute('data-action');
                                    var rel = ctxState.relative;
                                    hideFmMenu();
                                    if (!rel) {
                                        return;
                                    }
                                    if (action === 'open') {
                                        window.open(fmUrl(fmOpenBase, rel), '_blank', 'noopener,noreferrer');
                                    } else if (action === 'edit') {
                                        if (!ctxState.editable) {
                                            return;
                                        }
                                        openEditModal(rel);
                                    } else if (action === 'copy') {
                                        if (fmDupFrom && fmDupForm) {
                                            fmDupFrom.value = rel;
                                            fmDupForm.submit();
                                        }
                                    } else if (action === 'delete') {
                                        if (fmDelItem && fmDelForm && window.confirm('Delete this file? This cannot be undone.')) {
                                            fmDelItem.value = rel;
                                            fmDelForm.submit();
                                        }
                                    }
                                });
                            });
                        }

                        document.querySelectorAll('.file-row__name-text--file').forEach(function (span) {
                            span.addEventListener('click', function (e) {
                                if (span.querySelector('input')) return;
                                e.preventDefault();
                                var row = span.closest('.file-row');
                                var cb = row ? row.querySelector('input[type="checkbox"][name="items[]"]') : null;
                                if (cb) {
                                    cb.checked = true;
                                }
                            });
                            span.addEventListener('dblclick', function (e) {
                                e.preventDefault();
                                if (span.querySelector('input')) return;
                                var rel = span.getAttribute('data-relative');
                                var orig = span.textContent.trim();
                                var input = document.createElement('input');
                                input.type = 'text';
                                input.className = 'file-row__rename-input';
                                input.value = orig;
                                input.setAttribute('autocomplete', 'off');
                                span.textContent = '';
                                span.appendChild(input);
                                input.focus();
                                input.select();
                                var done = false;
                                function restore(text) {
                                    if (done) return;
                                    done = true;
                                    span.removeChild(input);
                                    span.textContent = text;
                                }
                                function save() {
                                    if (done) return;
                                    var newName = input.value.trim();
                                    if (newName === '' || newName === orig) {
                                        restore(orig);
                                        return;
                                    }
                                    done = true;
                                    span.removeChild(input);
                                    span.textContent = newName;
                                    fetch(renameUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken(),
                                            'X-Requested-With': 'XMLHttpRequest'
                                        },
                                        body: JSON.stringify({ from: rel, name: newName })
                                    }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                                        .then(function (res) {
                                            if (!res.ok || !res.data.ok) {
                                                var msg = 'Rename failed.';
                                                if (res.data) {
                                                    if (res.data.message) msg = res.data.message;
                                                    else if (res.data.errors && res.data.errors.name) msg = res.data.errors.name[0];
                                                }
                                                window.alert(msg);
                                                span.textContent = orig;
                                                return;
                                            }
                                                span.textContent = res.data.name;
                                            span.setAttribute('data-relative', res.data.relative);
                                            if (typeof res.data.editable !== 'undefined') {
                                                span.setAttribute('data-editable', res.data.editable ? '1' : '0');
                                            }
                                            var row = span.closest('.file-row');
                                            if (row && row.classList.contains('file-row--file')) {
                                                row.setAttribute('data-file-relative', res.data.relative);
                                                if (typeof res.data.editable !== 'undefined') {
                                                    row.setAttribute('data-file-editable', res.data.editable ? '1' : '0');
                                                }
                                            }
                                            var cb = row ? row.querySelector('input[type="checkbox"][name="items[]"]') : null;
                                            if (cb) cb.value = res.data.relative;
                                        })
                                        .catch(function () {
                                            window.alert('Rename failed.');
                                            span.textContent = orig;
                                        });
                                }
                                input.addEventListener('keydown', function (ev) {
                                    if (ev.key === 'Enter') {
                                        ev.preventDefault();
                                        save();
                                    } else if (ev.key === 'Escape') {
                                        ev.preventDefault();
                                        restore(orig);
                                    }
                                });
                                input.addEventListener('blur', function () {
                                    window.setTimeout(function () {
                                        if (!done && document.activeElement !== input) save();
                                    }, 0);
                                });
                            });
                        });
                    })();
                });
            </script>

            <div class="file-manager-layout">
                <aside class="file-manager-tree-panel" aria-label="Folder tree">
                    <div class="file-manager-tree-panel__sticky-head">
                        <h3 class="file-manager-tree-panel__title">Explore</h3>
                        <p class="file-manager-tree-panel__hint subtle">Expand folders or open a folder to view its files.</p>
                    </div>
                    <nav class="file-tree">
                        <div class="file-tree__root">
                            <a
                                href="{{ route('hosts.files.index', $hosting) }}"
                                class="file-tree__root-link @if ($listing['relativePath'] === '') is-active @endif"
                            ><i class="fa fa-home" aria-hidden="true"></i> Host root</a>
                        </div>
                        @if (count($listing['tree']) > 0)
                            @include('filemanager::partials.tree-nodes', [
                                'nodes' => $listing['tree'],
                                'hosting' => $hosting,
                                'currentPath' => $listing['relativePath'],
                            ])
                        @else
                            <p class="file-tree__empty subtle">No subfolders in the host root.</p>
                        @endif
                        @if ($listing['tree_truncated'])
                            <p class="file-tree__limit subtle">Tree limited for performance — use the list below for full paths.</p>
                        @endif
                    </nav>
                </aside>

                <div class="file-manager-main">
                    <div class="file-manager-main__sticky-head">
                        <nav class="file-manager-breadcrumb" aria-label="Folder path">
                            <a href="{{ route('hosts.files.index', $hosting) }}">Host root</a>
                            @foreach ($listing['breadcrumbs'] as $crumb)
                                <span class="file-manager-breadcrumb__sep" aria-hidden="true">/</span>
                                <a href="{{ route('hosts.files.index', ['hosting' => $hosting, 'path' => $crumb['path']]) }}">{{ $crumb['label'] }}</a>
                            @endforeach
                        </nav>

                        <div class="file-toolbar">
                            <h2>Contents @if ($listing['relativePath'] !== '')<span class="subtle">· {{ $listing['relativePath'] }}</span>@endif</h2>
                            @if ($listing['parentRelativePath'] !== null)
                                <a class="btn-secondary compact" href="{{ route('hosts.files.index', ['hosting' => $hosting, 'path' => $listing['parentRelativePath']]) }}">Up one level</a>
                            @endif
                        </div>
                    </div>

                    @if (count($listing['entries']) === 0)
                        <div class="file-manager-empty">
                            <p class="subtle">This folder is empty.</p>
                        </div>
                    @else
                        <div class="file-table file-table--selectable">
                            <div class="file-row file-row-head">
                                <span class="file-row__check" title="Select"></span>
                                <span>Name</span>
                                <span>Type</span>
                                <span>Size</span>
                                <span>Modified</span>
                            </div>
                            @foreach ($listing['entries'] as $entry)
                                <div
                                    class="file-row @unless ($entry['is_dir']) file-row--file @endunless"
                                    @unless ($entry['is_dir'])
                                        data-file-relative="{{ $entry['relative'] }}"
                                        data-file-editable="{{ $entry['editable'] ? '1' : '0' }}"
                                    @endunless
                                >
                                    <span class="file-row__check">
                                        <input
                                            type="checkbox"
                                            name="items[]"
                                            value="{{ $entry['relative'] }}"
                                            form="file-manager-bulk"
                                        >
                                    </span>
                                    <span class="file-row__name">
                                        @if ($entry['is_dir'])
                                            <i class="fa fa-folder file-row__icon" aria-hidden="true"></i>
                                            <a href="{{ route('hosts.files.index', ['hosting' => $hosting, 'path' => $entry['relative']]) }}">{{ $entry['name'] }}</a>
                                        @else
                                            <i class="fa fa-file-o file-row__icon" aria-hidden="true"></i>
                                            <span
                                                class="file-row__name-text file-row__name-text--file"
                                                data-relative="{{ $entry['relative'] }}"
                                                data-editable="{{ $entry['editable'] ? '1' : '0' }}"
                                                title="Right-click for menu · Click to select · Double-click to rename"
                                            >{{ $entry['name'] }}</span>
                                        @endif
                                    </span>
                                    <span>{{ $entry['is_dir'] ? 'Folder' : 'File' }}</span>
                                    <span>{{ $entry['is_dir'] ? '—' : \Illuminate\Support\Number::fileSize($entry['size'] ?? 0, 2) }}</span>
                                    <span>
                                        @if ($entry['mtime'])
                                            {{ \Illuminate\Support\Carbon::createFromTimestamp($entry['mtime'])->format('M j, Y H:i') }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <p class="file-manager-select-hint subtle">Right-click a <strong>file</strong> for Open, Edit, Copy, or Delete. Click a file name to select; double-click to rename. Use the toolbar for bulk actions.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
