@extends('layouts.host')

@section('title', 'PHP version - Xenweet')

@section('content')
<div class="host-panel-scope managedb-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Software</p>
            <h1>PHP version</h1>
            <p class="subtle">Applies to this hosting account’s <strong>website only</strong> (Nginx &rarr; PHP-FPM). SSH and CLI commands may use a different <code>php</code> binary on the server.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    <p class="ssltls-workflow-eyebrow" id="php-tools-tabs-h">PHP tools</p>
    <nav class="managedb-tabs ssltls-tool-tabs" aria-label="PHP tools tabs" aria-describedby="php-tools-tabs-h">
        <a href="{{ route('hosts.php-version', ['hosting' => $hosting, 'tab' => 'version']) }}" class="managedb-tab {{ $activeTab === 'version' ? 'is-active' : '' }}">PHP Version Changers</a>
        <a href="{{ route('hosts.php-version', ['hosting' => $hosting, 'tab' => 'extensions']) }}" class="managedb-tab {{ $activeTab === 'extensions' ? 'is-active' : '' }}">PHP Extensions</a>
        <a href="{{ route('hosts.php-version', ['hosting' => $hosting, 'tab' => 'options']) }}" class="managedb-tab {{ $activeTab === 'options' ? 'is-active' : '' }}">Options</a>
    </nav>

    @if (session('success'))
        <div class="server-card" style="border-left:4px solid var(--success-border, #16a34a); margin-bottom:1rem;">
            <p class="subtle" style="margin:0; color: inherit;">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="server-card" style="border-left:4px solid var(--danger-border, #dc2626); margin-bottom:1rem;">
            <p class="subtle" style="margin:0; color: inherit;">{{ session('error') }}</p>
        </div>
    @endif

    @if ($activeTab === 'version')
        <section class="server-card" id="php-version-changers">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">Web PHP for {{ $hosting->siteHost() }}</h2>
            <p class="subtle" style="margin-top:0.5rem;">Selected pool targets this socket (when not overridden in <code>config/hosting_provision.php</code>):</p>
            <p><code>{{ $fpmSocket }}</code></p>
            <p class="subtle" style="margin-top:0.5rem;">Nginx vhost regeneration: <strong>{{ $vhostEnabled ? 'enabled' : 'disabled' }}</strong>
                @unless ($vhostEnabled)
                    (set <code>HOSTING_VHOST_ENABLED=true</code> to apply the <strong>HTTP-only</strong> vhost on save, when you are not using SSL from this panel.)
                @endunless
            </p>
            <p class="subtle" style="margin-top:0.75rem; margin-bottom:0;">If you use <strong>HTTPS</strong> (PEM files under this account&rsquo;s <code>ssl/</code> from Auto SSL or <strong>Install certificate</strong>), saving here re-applies the <strong>full</strong> Nginx config (port 80 + 443) so the PHP pool matches and HTTPS keeps working. Only plain-HTTP sites use the HTTP-only vhost script.</p>
        </section>

        <section class="server-card" style="margin-top:1.25rem;">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">Change version</h2>
            <form class="managedb-form" method="post" action="{{ route('hosts.php-version.update', $hosting) }}">
                @csrf
                <label for="php_version" class="subtle">Version</label>
                <div style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center; margin-top:0.35rem;">
                    <select id="php_version" name="php_version" required style="min-width:12rem;">
                        @foreach ($allowedVersions as $v)
                            <option value="{{ $v }}" @selected($hosting->php_version === $v)>PHP {{ $v }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary">Save &amp; apply to web vhost</button>
                </div>
                @error('php_version')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin-top:0.5rem; margin-bottom:0;">{{ $message }}</p>
                @enderror
                <p class="subtle" style="margin-top:0.75rem; margin-bottom:0;">The server must run a <code>phpX.Y-fpm</code> pool and socket for the version you select (e.g. Debian <code>php8.2-fpm</code>). A missing pool typically returns HTTP 502.</p>
            </form>
        </section>
    @endif

    @if ($activeTab === 'extensions')
        <section class="server-card" id="php-extensions">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">PHP Extensions</h2>
            <p class="subtle" style="margin-top:0.35rem;">
                Enable or disable extension preferences for this hosting account.
            </p>

            <form class="managedb-form" method="post" action="{{ route('hosts.php-version.extensions.update', $hosting) }}">
                @csrf
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap:0.55rem; margin-top:0.75rem;">
                    @forelse ($availableExtensions as $ext)
                        @php $isEnabled = in_array($ext, $enabledExtensions, true); @endphp
                        <label style="display:flex; align-items:center; gap:0.45rem; border:1px solid var(--line); border-radius:10px; padding:0.55rem 0.65rem; background:rgba(255,255,255,0.03);">
                            <input type="checkbox" name="extensions[]" value="{{ $ext }}" @checked($isEnabled)>
                            <span style="display:flex; flex-direction:column; gap:0.08rem;">
                                <strong style="font-size:0.9rem; line-height:1.2;">{{ $ext }}</strong>
                                <small class="subtle" style="line-height:1.15;">{{ $isEnabled ? 'Enabled' : 'Disabled' }}</small>
                            </span>
                        </label>
                    @empty
                        <p class="subtle" style="margin:0;">No extensions configured in <code>config/phpversion.php</code>.</p>
                    @endforelse
                </div>

                @error('extensions')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin-top:0.6rem; margin-bottom:0;">{{ $message }}</p>
                @enderror
                @error('extensions.*')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin-top:0.6rem; margin-bottom:0;">{{ $message }}</p>
                @enderror

                <div style="display:flex; gap:0.65rem; align-items:center; margin-top:0.9rem;">
                    <button type="submit" class="btn-primary">Save extension options</button>
                    <a class="btn-secondary" href="{{ route('hosts.php-version', ['hosting' => $hosting, 'tab' => 'extensions']) }}">Refresh</a>
                </div>
            </form>
        </section>
    @endif

    @if ($activeTab === 'options')
        <section class="server-card" id="php-options">
            <h2 class="host-sidebar-meta-title" style="margin-top:0;">PHP ini options</h2>
            <p class="subtle" style="margin-top:0.35rem;">
                Edit host-specific php.ini preferences such as <code>allow_url_fopen</code>, <code>max_input_time</code>, and related limits.
            </p>

            <form class="managedb-form" method="post" action="{{ route('hosts.php-version.options.update', $hosting) }}">
                @csrf
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:0.75rem; margin-top:0.8rem;">
                    @forelse ($phpIniOptions as $opt)
                        <label style="display:flex; flex-direction:column; gap:0.35rem; border:1px solid var(--line); border-radius:10px; padding:0.62rem; background:rgba(255,255,255,0.03);">
                            <span style="font-weight:600; font-size:0.9rem;">{{ $opt['label'] }}</span>

                            @if ($opt['type'] === 'boolean')
                                <select name="ini[{{ $opt['key'] }}]">
                                    <option value="On" @selected(strtolower($opt['value']) === 'on')>On</option>
                                    <option value="Off" @selected(strtolower($opt['value']) === 'off')>Off</option>
                                </select>
                            @elseif ($opt['type'] === 'number')
                                <input
                                    type="number"
                                    name="ini[{{ $opt['key'] }}]"
                                    value="{{ old('ini.'.$opt['key'], $opt['value']) }}"
                                    min="0"
                                    step="1"
                                >
                            @else
                                <input
                                    type="text"
                                    name="ini[{{ $opt['key'] }}]"
                                    value="{{ old('ini.'.$opt['key'], $opt['value']) }}"
                                >
                            @endif

                            @if (! empty($opt['help']))
                                <small class="subtle">{{ $opt['help'] }}</small>
                            @endif
                        </label>
                    @empty
                        <p class="subtle" style="margin:0;">No ini options configured in <code>config/phpversion.php</code>.</p>
                    @endforelse
                </div>

                @error('ini')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin-top:0.6rem; margin-bottom:0;">{{ $message }}</p>
                @enderror
                @error('ini.*')
                    <p class="subtle" style="color: var(--danger-text, #b91c1c); margin-top:0.6rem; margin-bottom:0;">{{ $message }}</p>
                @enderror

                <div style="display:flex; gap:0.65rem; align-items:center; margin-top:0.9rem;">
                    <button type="submit" class="btn-primary">Save options</button>
                    <a class="btn-secondary" href="{{ route('hosts.php-version', ['hosting' => $hosting, 'tab' => 'options']) }}">Reset view</a>
                </div>
            </form>
        </section>
    @endif
</div>
@endsection
