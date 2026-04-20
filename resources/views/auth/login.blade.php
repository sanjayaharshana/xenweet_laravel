<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xenweet Hosting Panel - Login</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="brand-panel">
            <p class="eyebrow">Xenweet Hosting Control</p>
            <h1>Professional Web Hosting Control Panel</h1>
            <p>
                Monitor server health, service uptime, and web infrastructure in one modern control center.
            </p>
            <ul>
                <li>Secure authentication and role-ready architecture</li>
                <li>Realtime-like infrastructure stats dashboard</li>
                <li>Designed for hosting teams and devops workflows</li>
            </ul>
        </section>

        <section class="form-panel">
            <div class="form-panel-header">
                <h2>Sign in</h2>
                <p>Access your panel workspace</p>
            </div>

            @if ($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="auth-form">
                @csrf
                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>

                <label class="checkbox-row">
                    <input type="checkbox" name="remember" value="1">
                    <span>Remember this device</span>
                </label>

                <button type="submit" class="btn-primary">Login to Panel</button>
            </form>
        </section>
    </main>
</body>
</html>
