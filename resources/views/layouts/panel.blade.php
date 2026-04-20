<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Xenweet Hosting Panel')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body class="dashboard-body">
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

            <div class="nav-user">
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-secondary" type="submit">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="panel-shell">
        <aside class="left-sidebar">
            <p class="sidebar-title">Workspace</p>

            <div class="sidebar-group">
                <a href="{{ route('panel') }}" class="{{ request()->routeIs('panel') ? 'active' : '' }}">Hosting List</a>
                <a href="{{ route('plan.index') }}" class="{{ request()->routeIs('plan.*') ? 'active' : '' }}">Plans</a>
                <a href="{{ route('panel.logs') }}" class="{{ request()->routeIs('panel.logs') ? 'active' : '' }}">Logs</a>
                <a href="#">Account Security</a>
                <a href="#">Backups</a>
            </div>

            <div class="sidebar-card">
                <p>Logged in as</p>
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->email }}</span>
            </div>
        </aside>

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
</body>
</html>
