<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roundcube Auto Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #e2e8f0; padding: 24px; }
        .box { max-width: 700px; margin: 50px auto; background: #111827; border: 1px solid #334155; border-radius: 10px; padding: 20px; }
        .muted { color: #94a3b8; font-size: 14px; }
        a { color: #60a5fa; }
        code { background: #1f2937; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="box">
    <h2>Signing in to Roundcube...</h2>
    <p class="muted" id="status">Preparing secure login flow.</p>
    <p class="muted">If it does not continue, <a id="fallback" href="{{ $roundcubeBase }}?_task=login">open Roundcube login</a>.</p>
</div>

<script>
    (async function () {
        const status = document.getElementById('status');
        const base = @json($roundcubeBase);
        const user = @json($email);
        const pass = @json($password);

        function extractRequestToken(html) {
            try {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const byName = doc.querySelector('input[name="_token"]');
                if (byName && byName.value) {
                    return byName.value;
                }
            } catch (e) { /* continue */ }
            const patterns = [
                /name=["']_token["']\s+value=["']([^"']+)["']/i,
                /value=["']([^"']+)["']\s+name=["']_token["']/i,
                /["']request_token["']\s*:\s*["']([^"']+)["']/,
                /"request_token"\s*:\s*"([^"]+)"/,
                // Roundcube footer: set_env(\n{..."request_token":"...",...})
                /"request_token"\s*:\s*"((?:\\.|[^"\\])+)"/,
            ];
            for (let i = 0; i < patterns.length; i++) {
                const m = html.match(patterns[i]);
                if (m && m[1]) {
                    return m[1].replace(/\\(.)/g, (_, c) => c);
                }
            }
            return null;
        }

        try {
            const loginUrl = (base.indexOf('?') >= 0 ? base + '&' : base + '?') + '_task=login';
            const loginPageResponse = await fetch(loginUrl, {
                credentials: 'include',
                redirect: 'follow',
                cache: 'no-store',
            });
            const html = await loginPageResponse.text();
            if (!loginPageResponse.ok) {
                status.textContent = 'Auto login failed: Roundcube returned HTTP ' + loginPageResponse.status + '.';
                return;
            }
            const requestToken = extractRequestToken(html);
            if (!requestToken) {
                status.textContent = 'Auto login failed: Roundcube CSRF token not found (response length ' + html.length + '). Open Roundcube login manually.';
                return;
            }

            status.textContent = 'Submitting mailbox credentials to Roundcube...';
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = base;

            const fields = {
                _task: 'login',
                _action: 'login',
                _timezone: '_default_',
                _user: user,
                _pass: pass,
                _token: requestToken,
            };

            Object.entries(fields).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = String(value);
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        } catch (err) {
            status.textContent = 'Auto login failed: ' + (err && err.message ? err.message : 'Unknown error');
        }
    })();
</script>
</body>
</html>
