@extends('layouts.panel')

@section('title', 'WebMail Settings - Xenweet')

@section('content')
<div class="host-panel-scope settings-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Admin Settings</p>
            <h1>{{ $webmail['label'] ?? 'WebMail Settings' }}</h1>
            <p class="subtle">Manage common webmail host settings from one page.</p>
        </div>
    </header>

    @if (session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error">{{ $errors->first() }}</div>
    @endif

    <section class="server-card settings-panel">
        <div class="settings-content">
            <form method="POST" action="{{ route('panel.settings.webmail.update') }}" class="settings-form">
                @csrf

                @if (!empty($webmail['help']))
                    @include('panel.partials.settings-tab-help', ['help' => $webmail['help']])
                @endif

                @foreach ($fields as $field)
                    @include('panel.partials.settings-field', ['field' => $field, 'activeTab' => 'webmail_settings', 'settings' => $settings])
                @endforeach

                <div class="settings-actions">
                    <button type="submit" class="btn-primary">Save WebMail Settings</button>
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
            <h2 class="host-sidebar-meta-title">Roundcube Installer</h2>
            @php
                $roundcubeInstalled = is_file(public_path('roundcube/index.php'));
            @endphp
            <p class="subtle" style="margin-bottom:0.65rem;">
                Status: <strong>{{ $roundcubeInstalled ? 'Installed' : 'Not installed' }}</strong>
            </p>
            <form method="POST" action="{{ route('panel.settings.webmail.install-roundcube') }}">
                @csrf
                <button type="submit" class="btn-primary" style="width:100%;">Install Roundcube</button>
            </form>
            <form method="POST" action="{{ route('panel.settings.webmail.uninstall-roundcube') }}" style="margin-top:0.5rem;" onsubmit="return confirm('Uninstall Roundcube and remove central webmail files?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary" style="width:100%;">Uninstall Roundcube</button>
            </form>
            <p class="subtle" style="margin-top:0.65rem;">
                Source archive: <code>public/roundcubemail-1.5.15-complete.tar.gz</code>
            </p>
        </div>

        <div class="tips-panel tips-panel--nested">
            <h2>WebMail Notes</h2>
            <div class="tip-item">
                <h3>Central install</h3>
                <p>This installs Roundcube into <code>public/roundcube</code> for central webmail usage.</p>
            </div>
            <div class="tip-item">
                <h3>Per-host deploy</h3>
                <p>Per-host deploy mode still uses host-specific mail subdomains and host storage paths.</p>
            </div>
        </div>
    </div>
</div>
@endsection
