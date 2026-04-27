<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('xenweet-theme');
                if (t === 'light' || t === 'dark') {
                    document.documentElement.setAttribute('data-theme', t);
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <title>@yield('title', 'Xenweet Host')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
@php
    $isFileManager = request()->routeIs('hosts.files.*');
    $isFileManagerCodeEditor = request()->routeIs('hosts.files.code-editor');
    $showHostQuickSidebar = isset($hosting) && ! $isFileManager;
    $hasRightSidebar = ! $isFileManager && (view()->hasSection('right_sidebar') || isset($hosting));
    $moduleEnabled = function (string $name): bool {
        if (! class_exists(\Nwidart\Modules\Facades\Module::class)) {
            return false;
        }

        return \Nwidart\Modules\Facades\Module::isEnabled($name);
    };
@endphp
<body class="dashboard-body dashboard-body--host @if ($isFileManagerCodeEditor) dashboard-body--fm-code-editor @elseif ($isFileManager) dashboard-body--file-manager @endif">
    <nav class="host-main-navbar" aria-label="Host account">
        <div class="nav-inner host-nav-inner">
            @isset($hosting)
                <a href="{{ route('hosts.panel', $hosting) }}" class="host-nav-brand" title="Host overview">
                    <span class="host-nav-brand__mark" aria-hidden="true"></span>
                    <span class="host-nav-brand__text">
                        <span class="host-nav-brand__label">Host</span>
                        <span class="host-nav-brand__domain">{{ $hosting->domain }}</span>
                    </span>
                </a>
            @else
                <span class="host-nav-brand">
                    <span class="host-nav-brand__mark" aria-hidden="true"></span>
                    <span class="host-nav-brand__text">
                        <span class="host-nav-brand__label">Host</span>
                    </span>
                </span>
            @endisset

            <div class="host-nav-center">
                @isset($hosting)
                    <a href="{{ route('hosts.panel', $hosting) }}" class="{{ request()->routeIs('hosts.panel') ? 'active' : '' }}">Overview</a>
                    @if ($moduleEnabled('FileManager'))
                        <a href="{{ route('hosts.files.index', $hosting) }}" class="{{ request()->routeIs('hosts.files.*') ? 'active' : '' }}">Files</a>
                    @endif
                    @if ($moduleEnabled('ManageDb'))
                        <a href="{{ route('hosts.db.manage', $hosting) }}" class="{{ request()->routeIs('hosts.db.*') ? 'active' : '' }}">Databases</a>
                    @endif
                    @if ($moduleEnabled('PhpVersion'))
                        <a href="{{ route('hosts.php-version', $hosting) }}" class="{{ request()->routeIs('hosts.php-version*') ? 'active' : '' }}">PHP</a>
                    @endif
                    @if ($moduleEnabled('SslTls'))
                        <a href="{{ route('hosts.ssl-tls', $hosting) }}" class="{{ request()->routeIs('hosts.ssl-tls*') ? 'active' : '' }}">SSL</a>
                    @endif
                    @if ($moduleEnabled('Domains') && \Illuminate\Support\Facades\Route::has('hosts.domains.index'))
                        <a href="{{ route('hosts.domains.index', ['hosting' => $hosting, 'tab' => 'redirects']) }}" class="{{ request()->routeIs('hosts.domains.index') && request('tab') === 'redirects' ? 'active' : '' }}">Redirects</a>
                        <a href="{{ route('hosts.domains.index', ['hosting' => $hosting, 'tab' => 'zone']) }}" class="{{ request()->routeIs('hosts.domains.index') && request('tab') === 'zone' ? 'active' : '' }}">Zone Editor</a>
                    @endif
                    @if ($moduleEnabled('SshAccess'))
                        <a href="{{ route('hosts.ssh-access', $hosting) }}" class="{{ request()->routeIs('hosts.ssh-access*') ? 'active' : '' }}">SSH</a>
                        <a href="{{ route('hosts.terminal', $hosting) }}" class="{{ request()->routeIs('hosts.terminal') ? 'active' : '' }}">Terminal</a>
                    @endif
                @endisset
            </div>

            <div class="nav-right">
                <div class="nav-user">
                    @if (auth()->check())
                        <span>{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn-secondary" type="submit">Logout</button>
                        </form>
                    @elseif (isset($hosting))
                        <span>{{ $hosting->panel_username }}</span>
                        <form method="POST" action="{{ route('hosts.auth.logout', $hosting) }}">
                            @csrf
                            <button class="btn-secondary" type="submit">Logout</button>
                        </form>
                    @endif
                </div>
                <div class="nav-theme" role="group" aria-label="Color theme">
                    <button
                        type="button"
                        class="nav-theme-toggle"
                        id="theme-toggle"
                        title="Switch theme"
                        aria-label="Switch theme"
                    >
                        <i class="fa fa-sun-o" id="theme-toggle-icon" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="panel-shell panel-shell--no-left-sidebar @if ($showHostQuickSidebar) host-shell--with-left @endif">
        @if ($showHostQuickSidebar)
            <aside class="host-left-quicklinks" aria-label="Resources">
                <a href="#" class="host-left-quicklinks__item" title="Docs">
                    <i class="fa fa-book" aria-hidden="true"></i>
                    <span>Docs</span>
                </a>
                <a href="#" class="host-left-quicklinks__item" title="Customer Support">
                    <i class="fa fa-life-ring" aria-hidden="true"></i>
                    <span>Support</span>
                </a>
            </aside>
        @endif
        <section class="panel-content @if ($hasRightSidebar) panel-content-with-sidebar @if (request()->routeIs('hosts.panel')) panel-content-with-sidebar--compact @endif @endif">
            <div class="panel-main-content">
                @yield('content')
            </div>

            @if ($hasRightSidebar)
                <aside class="common-right-sidebar">
                    @hasSection('right_sidebar')
                        @yield('right_sidebar')
                    @elseif (isset($hosting))
                        <div class="host-panel-scope">
                            <div class="host-panel-sidebar">
                                <div class="server-card host-sidebar-meta">
                                    <h2 class="host-sidebar-meta-title">Host details</h2>
                                    <div class="meta meta--sidebar">
                                        <div><span>Domain</span><strong>{{ $hosting->domain }}</strong></div>
                                        <div><span>Server IP</span><strong>{{ $hosting->server_ip }}</strong></div>
                                        <div><span>Web root</span><strong>{{ $hosting->web_root_path ?: 'Not assigned yet' }}</strong></div>
                                    </div>
                                </div>

                                <div class="tips-panel tips-panel--nested">
                                    <h2>Tips</h2>
                                    <p class="subtle">Notes for this hosting account.</p>

                                    <div class="tip-item">
                                        <h3>Shortcuts</h3>
                                        <p>Use the top navigation to move between host tools quickly.</p>
                                    </div>
                                    <div class="tip-item">
                                        <h3>Web root</h3>
                                        <p>Public site files usually live under <code>public_html</code>.</p>
                                    </div>
                                    <div class="tip-item">
                                        <h3>Live site</h3>
                                        <p>Use <strong>Open Site</strong> for the domain; <strong>Open IP</strong> hits the server by IP only.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </aside>
            @endif
        </section>
    </div>
    <script>
        (function () {
            var KEY = 'xenweet-theme';
            var root = document.documentElement;

            function getTheme() {
                return root.getAttribute('data-theme') || 'dark';
            }

            function setTheme(t) {
                if (t !== 'light' && t !== 'dark') {
                    t = 'dark';
                }
                root.setAttribute('data-theme', t);
                try {
                    localStorage.setItem(KEY, t);
                } catch (e) {
                    /* ignore */
                }
                syncButton();
            }

            function syncButton() {
                var t = getTheme();
                var isLight = t === 'light';
                var btn = document.getElementById('theme-toggle');
                var icon = document.getElementById('theme-toggle-icon');
                if (btn) {
                    btn.setAttribute('aria-pressed', isLight ? 'true' : 'false');
                    var label = isLight ? 'Switch to dark mode' : 'Switch to light mode';
                    btn.title = label;
                    btn.setAttribute('aria-label', label);
                }
                if (icon) {
                    icon.className = isLight ? 'fa fa-moon-o' : 'fa fa-sun-o';
                }
            }

            var el = document.getElementById('theme-toggle');
            if (el) {
                el.addEventListener('click', function () {
                    setTheme(getTheme() === 'light' ? 'dark' : 'light');
                });
            }
            syncButton();
        })();
    </script>
</body>
</html>
