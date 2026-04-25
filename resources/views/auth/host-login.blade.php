<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Login - {{ $hosting->domain }}</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="brand-panel">
            <p class="eyebrow">Host Panel Access</p>
            <h1>{{ $hosting->domain }}</h1>
            <p>Sign in with this hosting account credentials.</p>
            <ul>
                <li>Separate login for each hosting account</li>
                <li>Access only this host's tools and settings</li>
                <li>Admin account login is not required here</li>
            </ul>
        </section>

        <section class="form-panel">
            <div class="form-panel-header">
                <h2>Host Sign in</h2>
                <p>Enter username and password from hosting record</p>
            </div>

            @if (session('host_auth_notice'))
                <div class="alert error">{{ session('host_auth_notice') }}</div>
            @endif
            @if (session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('hosts.auth.login.attempt', $hosting) }}" class="auth-form">
                @csrf
                <label for="panel_username">Username</label>
                <input id="panel_username" name="panel_username" type="text" value="{{ old('panel_username') }}" required autofocus>

                <label for="panel_password">Password</label>
                <input id="panel_password" name="panel_password" type="password" required>

                <button type="submit" class="btn-primary">Open Host Panel</button>
            </form>
        </section>
    </main>
</body>
</html>
