<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host 2FA - {{ $hosting->domain }}</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="brand-panel">
            <p class="eyebrow">Host Panel 2FA</p>
            <h1>{{ $hosting->domain }}</h1>
            <p>Enter the 6-digit code from your authenticator app.</p>
            <ul>
                <li>Use your authenticator app code</li>
                <li>Or use one recovery code</li>
                <li>Recovery codes are single-use</li>
            </ul>
        </section>

        <section class="form-panel">
            <div class="form-panel-header">
                <h2>Two-factor verification</h2>
                <p>Complete login for this host account</p>
            </div>

            @if ($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
            @endif
            @if (session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('hosts.auth.2fa.verify', $hosting) }}" class="auth-form">
                @csrf
                <label for="code">Authenticator or recovery code</label>
                <input id="code" name="code" type="text" maxlength="32" required autofocus>
                <button type="submit" class="btn-primary">Verify and open host panel</button>
            </form>

            <form method="GET" action="{{ route('hosts.auth.login', $hosting) }}" style="margin-top:0.6rem">
                <button type="submit" class="btn-secondary">Back to login</button>
            </form>
        </section>
    </main>
</body>
</html>
