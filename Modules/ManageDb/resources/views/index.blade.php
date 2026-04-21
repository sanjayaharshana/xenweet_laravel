@extends('layouts.panel')

@section('title', 'Manage DB - Xenweet')

@section('content')
<div class="host-panel-scope managedb-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Manage DB</p>
            <h1>{{ $hosting->domain }}</h1>
            <p class="subtle">Create MySQL databases and users with host prefix.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    <section class="server-card managedb-prefix-card">
        <div class="managedb-prefix-card__icon"><i class="fa fa-shield"></i></div>
        <div>
            <p class="managedb-prefix-card__title">Prefix protection enabled</p>
            <p class="managedb-prefix-card__text">Every database and user will be created as <code>{{ $prefix }}_your_name</code>.</p>
        </div>
    </section>

    @if (session('success'))
        <div class="flash-success managedb-flash">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error managedb-flash">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($loadError)
        <div class="alert error managedb-flash">
            Could not load current databases/users: {{ $loadError }}
        </div>
    @endif

    <div class="managedb-grid managedb-grid--forms">
        <section class="server-card managedb-card">
            <div class="managedb-card__head">
                <h2>Create Database</h2>
                <p>New database name will include host prefix automatically.</p>
            </div>
            <form class="managedb-form" method="POST" action="{{ route('hosts.db.create-database', $hosting) }}">
                @csrf
                <label for="db-name">Database name (without prefix)</label>
                <div class="managedb-input-prefix">
                    <span>{{ $prefix }}_</span>
                    <input id="db-name" type="text" name="name" placeholder="app" required>
                </div>
                <div class="managedb-actions">
                    <button class="btn-primary" type="submit">Create Database</button>
                </div>
            </form>
        </section>

        <section class="server-card managedb-card">
            <div class="managedb-card__head">
                <h2>Create MySQL User</h2>
                <p>Create user and optionally grant privileges to one database.</p>
            </div>
            <form class="managedb-form" method="POST" action="{{ route('hosts.db.create-user', $hosting) }}">
                @csrf
                <label for="user-name">User name (without prefix)</label>
                <div class="managedb-input-prefix">
                    <span>{{ $prefix }}_</span>
                    <input id="user-name" type="text" name="name" placeholder="appuser" required>
                </div>

                <label for="user-password">Password</label>
                <input id="user-password" type="text" name="password" placeholder="Strong password" required>

                <label for="user-database">Grant to database (optional)</label>
                <select id="user-database" name="database">
                    <option value="">-- no grant now --</option>
                    @foreach ($databases as $database)
                        <option value="{{ $database }}">{{ $database }}</option>
                    @endforeach
                </select>
                <div class="managedb-actions">
                    <button class="btn-primary" type="submit">Create User</button>
                </div>
            </form>
        </section>
    </div>

    <div class="managedb-grid managedb-grid--lists">
        <section class="server-card managedb-card">
            <div class="managedb-card__head">
                <h3>Databases with prefix</h3>
            </div>
            @if (count($databases) === 0)
                <p class="managedb-empty">No databases found.</p>
            @else
                <ul class="managedb-list">
                    @foreach ($databases as $database)
                        <li><code>{{ $database }}</code></li>
                    @endforeach
                </ul>
            @endif
        </section>
        <section class="server-card managedb-card">
            <div class="managedb-card__head">
                <h3>Users with prefix</h3>
            </div>
            @if (count($users) === 0)
                <p class="managedb-empty">No users found.</p>
            @else
                <ul class="managedb-list">
                    @foreach ($users as $user)
                        <li><code>{{ $user }}</code></li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
@endsection
