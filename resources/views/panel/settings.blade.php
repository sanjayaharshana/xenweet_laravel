@extends('layouts.panel')

@section('title', 'Settings - Xenweet')

@section('content')
<div class="host-panel-scope settings-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Admin Settings</p>
            <h1>Platform Configuration</h1>
            <p class="subtle">Manage panel behavior with dynamic tab-based settings.</p>
        </div>
    </header>

    @if (session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error">{{ $errors->first() }}</div>
    @endif

    <section class="server-card settings-panel">
        <aside class="settings-tabs" role="tablist" aria-label="Settings tabs">
            @foreach ($tabs as $key => $tab)
                <a
                    href="{{ route('panel.settings', ['tab' => $key]) }}"
                    role="tab"
                    aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                    class="settings-tab {{ $activeTab === $key ? 'is-active' : '' }}"
                >
                    <i class="{{ $tab['icon'] ?? 'fa fa-cog' }}" aria-hidden="true"></i>
                    <span>{{ $tab['label'] ?? ucfirst($key) }}</span>
                </a>
            @endforeach
        </aside>

        <div class="settings-content">
            <form method="POST" action="{{ route('panel.settings.update') }}" class="settings-form" data-test-url="{{ route('panel.settings.test-db') }}">
                @csrf
                <input type="hidden" name="tab" value="{{ $activeTab }}">

                @php
                    $activeFields = $tabs[$activeTab]['fields'] ?? [];
                @endphp

                @if ($activeTab === 'db_management')
                    @php
                        $dbCards = [
                            ['prefix' => 'postgres_', 'title' => 'PostgreSQL', 'icon' => 'fa fa-database'],
                            ['prefix' => 'mysql_', 'title' => 'MySQL', 'icon' => 'fa fa-server'],
                            ['prefix' => 'sqlite_', 'title' => 'SQLite', 'icon' => 'fa fa-file-text-o'],
                            ['prefix' => 'central_db_', 'title' => 'Central DB', 'icon' => 'fa fa-sitemap'],
                        ];
                    @endphp
                    <div class="settings-db-cards">
                        @foreach ($dbCards as $card)
                            <section class="settings-db-card" data-db-card data-db-type="{{ rtrim($card['prefix'], '_') }}">
                                <header class="settings-db-card__head">
                                    <h3><i class="{{ $card['icon'] }}"></i> {{ $card['title'] }}</h3>
                                </header>
                                @foreach ($activeFields as $field)
                                    @if (str_starts_with($field['key'] ?? '', $card['prefix']))
                                        @include('panel.partials.settings-field', ['field' => $field, 'activeTab' => $activeTab, 'settings' => $settings])
                                    @endif
                                @endforeach
                                <div class="settings-db-card__actions">
                                    <button type="button" class="btn-secondary compact-btn settings-db-test-btn" data-db-prefix="{{ $card['prefix'] }}">
                                        Test Connection
                                    </button>
                                    <span class="settings-db-test-result" data-db-result></span>
                                </div>
                            </section>
                        @endforeach
                    </div>
                @else
                    @foreach ($activeFields as $field)
                        @include('panel.partials.settings-field', ['field' => $field, 'activeTab' => $activeTab, 'settings' => $settings])
                    @endforeach
                @endif

                <div class="settings-actions">
                    <button type="submit" class="btn-primary">Save {{ $tabs[$activeTab]['label'] ?? ucfirst($activeTab) }} Settings</button>
                </div>
            </form>

            <script>
                (function () {
                    const form = document.querySelector('.settings-form');
                    if (!form) {
                        return;
                    }

                    const updateSwitchText = () => {
                        form.querySelectorAll('.settings-switch').forEach((el) => {
                            const checkbox = el.querySelector('input[type="checkbox"]');
                            const text = el.querySelector('.settings-switch__text');
                            if (checkbox && text) {
                                text.textContent = checkbox.checked ? 'Enabled' : 'Disabled';
                            }
                        });
                    };

                    const updateDependentFields = () => {
                        form.querySelectorAll('[data-depends-on]').forEach((fieldWrap) => {
                            const dependsKey = fieldWrap.getAttribute('data-depends-on');
                            const controller = form.querySelector('[name="settings[' + dependsKey + ']"]');
                            const enabled = controller ? controller.checked : false;
                            fieldWrap.classList.toggle('is-hidden', !enabled);

                            fieldWrap.querySelectorAll('input, select, textarea').forEach((input) => {
                                if (enabled) {
                                    input.removeAttribute('disabled');
                                } else {
                                    input.setAttribute('disabled', 'disabled');
                                }
                            });
                        });
                    };

                    form.addEventListener('change', () => {
                        updateSwitchText();
                        updateDependentFields();
                    });

                    form.querySelectorAll('.settings-db-test-btn').forEach((btn) => {
                        btn.addEventListener('click', async () => {
                            const prefix = btn.getAttribute('data-db-prefix') || '';
                            const dbType = prefix.replace(/_$/, '');
                            const card = btn.closest('[data-db-card]');
                            const resultEl = card ? card.querySelector('[data-db-result]') : null;

                            const enabledToggle = form.querySelector('[name="settings[' + prefix + 'enabled]"]');
                            if (enabledToggle && !enabledToggle.checked) {
                                if (resultEl) {
                                    resultEl.textContent = 'Enable this DB first.';
                                    resultEl.classList.remove('is-ok');
                                    resultEl.classList.add('is-error');
                                }
                                return;
                            }

                            const payload = {};
                            ['host', 'port', 'database', 'username', 'password'].forEach((key) => {
                                const input = form.querySelector('[name="settings[' + prefix + key + ']"]');
                                payload[key] = input ? input.value : '';
                            });

                            if (resultEl) {
                                resultEl.textContent = 'Testing...';
                                resultEl.classList.remove('is-ok', 'is-error');
                            }

                            try {
                                const response = await fetch(form.dataset.testUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        db_type: dbType,
                                        settings: payload,
                                    }),
                                });

                                const data = await response.json();
                                if (!response.ok || !data.ok) {
                                    throw new Error(data.message || 'Connection failed.');
                                }

                                if (resultEl) {
                                    resultEl.textContent = data.message || 'Connection successful.';
                                    resultEl.classList.remove('is-error');
                                    resultEl.classList.add('is-ok');
                                }
                            } catch (error) {
                                if (resultEl) {
                                    resultEl.textContent = error.message || 'Connection failed.';
                                    resultEl.classList.remove('is-ok');
                                    resultEl.classList.add('is-error');
                                }
                            }
                        });
                    });

                    updateSwitchText();
                    updateDependentFields();
                })();
            </script>
        </div>
    </section>
</div>
@endsection

@section('right_sidebar')
<div class="host-panel-scope settings-scope">
    <div class="host-panel-sidebar">
        <div class="server-card host-sidebar-meta">
            <h2 class="host-sidebar-meta-title">Settings Overview</h2>
            <div class="meta meta--sidebar">
                <div>
                    <span>Active tab</span>
                    <strong>{{ $tabs[$activeTab]['label'] ?? ucfirst($activeTab) }}</strong>
                </div>
                <div>
                    <span>Total tabs</span>
                    <strong>{{ count($tabs) }}</strong>
                </div>
                <div>
                    <span>Fields in tab</span>
                    <strong>{{ count($tabs[$activeTab]['fields'] ?? []) }}</strong>
                </div>
            </div>
        </div>

        <div class="tips-panel tips-panel--nested">
            <h2>Settings Help</h2>
            <p class="subtle">Quick notes for this page.</p>

            <div class="tip-item">
                <h3>Dynamic tabs</h3>
                <p>Tabs and fields are loaded from <code>config/admin_settings.php</code>.</p>
            </div>
            <div class="tip-item">
                <h3>Save behavior</h3>
                <p>Values are stored when you click <strong>Save</strong> for the current tab.</p>
            </div>
            <div class="tip-item">
                <h3>Navigation</h3>
                <p>Switch tabs from the top bar to update only that tab's settings.</p>
            </div>
        </div>
    </div>
</div>
@endsection
