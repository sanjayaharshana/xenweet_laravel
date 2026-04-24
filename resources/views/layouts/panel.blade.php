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
    <title>@yield('title', 'Xenweet Hosting Panel')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
@php
    $isFileManager = request()->routeIs('hosts.files.*');
@endphp
<body class="dashboard-body @if ($isFileManager) dashboard-body--file-manager @endif">
    @unless ($isFileManager)
        <nav class="main-navbar">
            <div class="nav-inner">
                <a href="{{ route('panel') }}" class="brand">
                    <span class="brand-dot"></span>
                    Xenweet Panel
                </a>

                <div class="nav-links">
                    <a href="{{ route('panel') }}" class="{{ request()->routeIs('panel') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('panel.logs') }}" class="{{ request()->routeIs('panel.logs') ? 'active' : '' }}">Logs</a>
                    <a href="#">Documentation</a>
                    <a href="#">Customer Support</a>
                    <span class="disabled-link nav-disabled-link" aria-disabled="true" title="Coming soon">Reseller Program</span>
                </div>

                <div class="nav-right">
                    <div class="nav-user">
                        <span>{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn-secondary" type="submit">Logout</button>
                        </form>
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
    @else
        <div class="nav-theme nav-theme--floating" role="group" aria-label="Color theme">
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
    @endunless

    <div class="panel-shell @if ($isFileManager) panel-shell--no-left-sidebar @endif">
        @unless ($isFileManager)
            <aside class="left-sidebar">
                <p class="sidebar-title">Workspace</p>

                <div class="sidebar-group">
                    <a href="{{ route('panel') }}" class="{{ request()->routeIs('panel') ? 'active' : '' }}">
                        <i class="fa fa-th-list" aria-hidden="true"></i><span>Hosting List</span>
                    </a>
                    <a href="{{ route('plan.index') }}" class="{{ request()->routeIs('plan.*') ? 'active' : '' }}">
                        <i class="fa fa-cubes" aria-hidden="true"></i><span>Plans</span>
                    </a>
                    <a href="{{ route('panel.logs') }}" class="{{ request()->routeIs('panel.logs') ? 'active' : '' }}">
                        <i class="fa fa-file-text-o" aria-hidden="true"></i><span>Logs</span>
                    </a>
                    <a href="{{ route('panel.settings') }}" class="{{ request()->routeIs('panel.settings*') ? 'active' : '' }}">
                        <i class="fa fa-cog" aria-hidden="true"></i><span>Settings</span>
                    </a>
                    <a href="#">
                        <i class="fa fa-shield" aria-hidden="true"></i><span>Account Security</span>
                    </a>
                    <a href="#">
                        <i class="fa fa-hdd-o" aria-hidden="true"></i><span>Backups</span>
                    </a>
                </div>

                <div class="sidebar-card">
                    <p>Logged in as</p>
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>{{ auth()->user()->email }}</span>
                </div>
            </aside>
        @endunless

        <section class="panel-content @if (view()->hasSection('right_sidebar')) panel-content-with-sidebar @if (request()->routeIs('hosts.panel')) panel-content-with-sidebar--compact @endif @endif">
            <div class="panel-main-content">
                @yield('content')
            </div>

            @hasSection('right_sidebar')
                <aside class="common-right-sidebar">
                    @yield('right_sidebar')
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
