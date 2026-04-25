@extends('layouts.panel')

@section('title', 'SSL / TLS Manager - Xenweet')

@section('content')
<div class="host-panel-scope ssltls-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Security</p>
            <h1>SSL / TLS Manager</h1>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    @if (session('ssltls_success'))
        <div class="flash-success managedb-flash" role="status">{{ session('ssltls_success') }}</div>
    @endif
    @if (session('ssltls_error'))
        <div class="alert error managedb-flash" role="alert">{{ session('ssltls_error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert error managedb-flash" role="alert">
            @foreach ($errors->all() as $message)
                <p style="margin:0 0 0.35rem;">{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <section class="server-card ssltls-intro" aria-labelledby="ssltls-intro-h">
        <h2 class="ssltls-intro__title" id="ssltls-intro-h">How the pieces work together</h2>
        <p class="subtle ssltls-intro__lede">
            A <strong>private key</strong> stays on the server and must remain secret. A <strong>CSR</strong> is a request you (or the panel) build from
            that key and send to a certificate authority. The CA returns an <strong>SSL certificate</strong> you install so browsers trust
            <code>{{ $hosting->siteHost() }}</code> for HTTPS. This panel can store the key, CSR, host names, and certificate in your
            <strong>hosting account record</strong> (the key is encrypted) so you can return and copy them; still protect the database and your login.
            Self-signed certificates are fine for testing, but visitors will see a warning until you use a publicly trusted certificate.
        </p>
    </section>

    <section class="server-card ssltls-card">
        <div class="ssltls-card__row">
            <div class="ssltls-card__icon" aria-hidden="true"><i class="fa fa-globe"></i></div>
            <div>
                <h2 class="ssltls-card__title">Site for this host</h2>
                <p class="subtle">Hostname: <code>{{ $hosting->siteHost() }}</code></p>
                <p class="ssltls-link-row">
                    <a href="{{ $httpsUrl }}" class="ssltls-link" target="_blank" rel="noopener noreferrer">{{ $httpsUrl }}</a>
                    <span class="ssltls-sep" aria-hidden="true">|</span>
                    <a href="{{ $httpUrl }}" class="ssltls-link ssltls-link--http" target="_blank" rel="noopener noreferrer">{{ $httpUrl }}</a>
                </p>
            </div>
        </div>
    </section>

    <p class="ssltls-workflow-eyebrow" id="ssltls-tools-h">SSL / TLS tools</p>
    <nav class="managedb-tabs ssltls-tool-tabs" aria-label="SSL / TLS tools" aria-describedby="ssltls-tools-h">
        <a
            href="{{ route('hosts.ssl-tls', $hosting) }}?tab=hosts"
            class="managedb-tab {{ $activeToolTab === 'hosts' ? 'is-active' : '' }}"
        >
            <i class="fa fa-server" aria-hidden="true"></i> Manage SSL Hosts
        </a>
        <a
            href="{{ route('hosts.ssl-tls', $hosting) }}?tab=key"
            class="managedb-tab {{ $activeToolTab === 'key' ? 'is-active' : '' }}"
        >
            <i class="fa fa-key" aria-hidden="true"></i> Private key
        </a>
        <a
            href="{{ route('hosts.ssl-tls', $hosting) }}?tab=csr"
            class="managedb-tab {{ $activeToolTab === 'csr' ? 'is-active' : '' }}"
        >
            <i class="fa fa-file-text-o" aria-hidden="true"></i> CSR
        </a>
        <a
            href="{{ route('hosts.ssl-tls', $hosting) }}?tab=auto"
            class="managedb-tab {{ $activeToolTab === 'auto' ? 'is-active' : '' }}"
        >
            <i class="fa fa-bolt" aria-hidden="true"></i> Auto SSL
        </a>
        <a
            href="{{ route('hosts.ssl-tls', $hosting) }}?tab=cert"
            class="managedb-tab {{ $activeToolTab === 'cert' ? 'is-active' : '' }}"
        >
            <i class="fa fa-certificate" aria-hidden="true"></i> SSL certificate
        </a>
    </nav>

    @if ($activeToolTab === 'hosts')
        <section class="server-card managedb-card ssltls-tool" aria-labelledby="ssltls-panel-hosts-h" role="region">
            <div class="managedb-card__head">
                <h2 id="ssltls-panel-hosts-h"><i class="fa fa-server" aria-hidden="true"></i> Manage SSL Hosts</h2>
                <p>Define which hostnames this certificate should cover. The <strong>primary</strong> domain is always included; add <strong>subject alternative names (SANs)</strong> for extra names (for example <code>www</code> or other sites on the same server).</p>
            </div>
            <div class="ssltls-san-table-wrap">
                <table class="ssltls-san-table" role="table" aria-label="Primary hostname">
                    <thead>
                        <tr>
                            <th scope="col">Role</th>
                            <th scope="col">Hostname</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="ssltls-san-pill">Primary</span></td>
                            <td><code>{{ $hosting->siteHost() }}</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <form class="managedb-form" method="POST" action="{{ route('hosts.ssl-tls.san-hostnames', $hosting) }}">
                @csrf
                <label for="ssltls-san-hostnames">Additional hostnames (SANs), one per line</label>
                <textarea
                    id="ssltls-san-hostnames"
                    name="san_hostnames"
                    class="ssltls-textarea"
                    rows="6"
                    spellcheck="false"
                    placeholder="www.{{ $hosting->siteHost() }}&#10;cdn.{{ $hosting->siteHost() }}"
                >{{ old('san_hostnames', $sslSanHostnamesText) }}</textarea>
                <p class="ssltls-hint">Do not repeat the primary name here. These names are stored on your account with the other SSL material. Match them in DNS and on the web server. Installing the cert on the live server is still a separate step.</p>
                <div class="managedb-actions">
                    <button type="submit" class="btn-primary">Save host names</button>
                </div>
            </form>
        </section>
    @elseif ($activeToolTab === 'key')
        <section class="server-card managedb-card ssltls-tool" aria-labelledby="ssltls-panel-key-h" role="region">
            <div class="managedb-card__head">
                <h2 id="ssltls-panel-key-h"><i class="fa fa-key" aria-hidden="true"></i> Private key</h2>
                <p>Generate a new key pair&rsquo;s private half (via local <code>openssl</code> through a shell helper). It stays on the server and must never be shared or committed to source control.</p>
            </div>
            <form class="managedb-form" method="POST" action="{{ route('hosts.ssl-tls.generate-key', $hosting) }}">
                @csrf
                <label for="ssltls-key-type">Key type</label>
                <select id="ssltls-key-type" name="key_type" required>
                    <option value="rsa2048" {{ old('key_type', 'rsa2048') === 'rsa2048' ? 'selected' : '' }}>RSA 2048 (recommended)</option>
                    <option value="ec256" {{ old('key_type') === 'ec256' ? 'selected' : '' }}>EC P-256 (prime256v1)</option>
                </select>
                <label for="ssltls-key-out">PEM (private key)</label>
                <div class="ssltls-pem-frame">
                    <textarea
                        id="ssltls-key-out"
                        class="ssltls-textarea ssltls-textarea--pem ssltls-textarea--readonly"
                        rows="10"
                        readonly
                        placeholder="Click &ldquo;Generate private key&rdquo; to create a key&hellip;"
                        spellcheck="false"
                    >{{ session('ssltls_key_pem', $sslStore?->private_key_pem) }}</textarea>
                </div>
                <p class="ssltls-hint">The private key is stored <strong>encrypted</strong> in the database for this host. Keep a copy in a password manager or secure deploy path; do not share it or commit it to source control.</p>
                <div class="managedb-actions">
                    <button type="submit" class="btn-primary">Generate private key</button>
                </div>
            </form>
        </section>
    @elseif ($activeToolTab === 'csr')
        <section class="server-card managedb-card ssltls-tool" aria-labelledby="ssltls-panel-csr-h" role="region">
            <div class="managedb-card__head">
                <h2 id="ssltls-panel-csr-h"><i class="fa fa-file-text-o" aria-hidden="true"></i> Certificate signing request (CSR)</h2>
                <p>Build a CSR from your private key (PEM) to send to a certificate authority. Uses <code>openssl req -new</code> via the same shell helper.</p>
            </div>
            <form class="managedb-form" method="POST" action="{{ route('hosts.ssl-tls.generate-csr', $hosting) }}">
                @csrf
                <label for="ssltls-private-key">Private key (PEM)</label>
                <div class="ssltls-pem-frame">
                    <textarea
                        id="ssltls-private-key"
                        class="ssltls-textarea ssltls-textarea--pem"
                        name="private_key"
                        rows="8"
                        required
                        placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"
                        spellcheck="false"
                    >{{ old('private_key', $sslStore?->private_key_pem) }}</textarea>
                </div>
                <label for="ssltls-csr-cn">Common name (CN) &mdash; usually your site hostname</label>
                <input id="ssltls-csr-cn" type="text" name="common_name" value="{{ old('common_name', $hosting->siteHost()) }}" required autocomplete="off" pattern="[a-zA-Z0-9.\-]+" maxlength="253">
                <label for="ssltls-csr-country">Country (C), ISO-3166-1 alpha-2 (optional)</label>
                <input id="ssltls-csr-country" type="text" name="country" value="{{ old('country') }}" placeholder="e.g. US" maxlength="2" pattern="[A-Za-z]{0,2}" inputmode="text" autocomplete="off">
                <label for="ssltls-csr-state">State / province (ST, optional)</label>
                <input id="ssltls-csr-state" type="text" name="state" value="{{ old('state') }}" maxlength="128" autocomplete="off">
                <label for="ssltls-csr-locality">Locality / city (L, optional)</label>
                <input id="ssltls-csr-locality" type="text" name="locality" value="{{ old('locality') }}" maxlength="128" autocomplete="off">
                <label for="ssltls-csr-org">Organization (O, optional)</label>
                <input id="ssltls-csr-org" type="text" name="organization" value="{{ old('organization') }}" maxlength="64" autocomplete="organization">
                <label for="ssltls-csr-ou">Organizational unit (OU, optional)</label>
                <input id="ssltls-csr-ou" type="text" name="organizational_unit" value="{{ old('organizational_unit') }}" maxlength="64" autocomplete="off">
                <label for="ssltls-csr-email">Email (optional, in CSR subject)</label>
                <input id="ssltls-csr-email" type="email" name="email" value="{{ old('email') }}" maxlength="254" autocomplete="off">
                <label for="ssltls-csr-out">PEM (certificate signing request)</label>
                <div class="ssltls-pem-frame">
                    <textarea
                        id="ssltls-csr-out"
                        class="ssltls-textarea ssltls-textarea--pem ssltls-textarea--readonly"
                        rows="10"
                        readonly
                        placeholder="Run &ldquo;Generate CSR&rdquo; after pasting your private key&hellip;"
                        spellcheck="false"
                    >{{ session('ssltls_csr_pem', $sslStore?->csr_pem) }}</textarea>
                </div>
                <p class="ssltls-hint">After a successful request, the CSR and the private key you used are stored for this host (key encrypted) so you can copy them when you return.</p>
                <div class="managedb-actions">
                    <button type="submit" class="btn-primary">Generate CSR</button>
                </div>
            </form>
            <section class="ssltls-csr-actions" aria-labelledby="ssltls-csr-actions-h">
                <h3 id="ssltls-csr-actions-h">Submit CSR to a Certificate Authority</h3>
                <p class="ssltls-hint">Use these inbuilt actions to send your CSR to a CA quickly.</p>
                <div class="managedb-actions">
                    <button type="button" class="btn-secondary" id="ssltls-copy-csr-btn">Copy CSR</button>
                    <a class="btn-secondary" href="{{ route('hosts.ssl-tls.download-csr', $hosting) }}">Download CSR (.pem)</a>
                </div>
                <div class="ssltls-ca-links">
                    <a href="https://www.digicert.com/kb/ssl-support/csr-generation-for-web-hosting.htm" target="_blank" rel="noopener noreferrer">DigiCert</a>
                    <a href="https://www.sectigo.com/ssl-certificates-tls" target="_blank" rel="noopener noreferrer">Sectigo</a>
                    <a href="https://letsencrypt.org/" target="_blank" rel="noopener noreferrer">Let's Encrypt (ACME)</a>
                </div>
            </section>
        </section>
    @elseif ($activeToolTab === 'auto')
        <section class="server-card managedb-card ssltls-tool" aria-labelledby="ssltls-auto-h" role="region">
            <div class="managedb-card__head">
                <h2 id="ssltls-auto-h"><i class="fa fa-bolt" aria-hidden="true"></i> Let’s Encrypt (Auto SSL)</h2>
                <p>Obtain a free, publicly trusted certificate on this app server with <code>certbot</code> (ACME). Same outcome as <strong>Install certificate</strong>: PEM files under the host&rsquo;s <code>ssl</code> directory, then the same Nginx install step (HTTPS vhost, correct PHP-FPM for this site&rsquo;s <code>php_version</code>) and reload when configured.</p>
            </div>

            @if ($letsEncryptEnabled ?? false)
                <div class="ssltls-auto">
                    <div class="ssltls-auto__head">
                        <div class="ssltls-auto__icon" aria-hidden="true">
                            <i class="fa fa-shield" aria-hidden="true"></i>
                        </div>
                        <div class="ssltls-auto__head-text">
                            <h3 class="ssltls-auto__title">One-click request</h3>
                            <p class="ssltls-auto__lede subtle">
                                Uses HTTP validation on port 80. <a href="{{ route('hosts.ssl-tls', $hosting) }}?tab=hosts" class="ssltls-auto__link">Manage SSL Hosts</a> to add <abbr title="Subject Alternative Name">SAN</abbr> names.
                            </p>
                            <div class="ssltls-auto__meta">
                                @if ($letsEncryptStagingConfig)
                                    <span class="ssltls-pill ssltls-pill--amber" title="Panel .env: SSLTLS_LETSENCRYPT_STAGING">Staging CA</span>
                                @endif
                                @if ($sslStore?->letsencrypt_issued_at)
                                    <span class="ssltls-pill ssltls-pill--ok" role="status">
                                        Issued {{ $sslStore->letsencrypt_issued_at->toDayDateTimeString() }}
                                        @if ($sslStore->letsencrypt_staging)
                                            (staging)
                                        @endif
                                    </span>
                                @else
                                    <span class="ssltls-pill">Not issued yet</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="ssltls-auto__section">
                        <h4 class="ssltls-auto__h4">Hostnames on this certificate</h4>
                        <ul class="ssltls-auto-domains" role="list">
                            @foreach ($letsEncryptDomainList as $d)
                                <li>
                                    <code>{{ $d }}</code>
                                    @if ($d === $hosting->siteHost())
                                        <span class="ssltls-auto-domains__tag">primary</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="ssltls-auto__section">
                        <h4 class="ssltls-auto__h4">Server checks</h4>
                        <ul class="ssltls-req" role="list">
                            <li>
                                <span class="ssltls-req__ic" aria-hidden="true">✓</span>
                                <span>DNS for each name resolves to <strong>this</strong> server (the panel machine).</span>
                            </li>
                            <li>
                                <span class="ssltls-req__ic" aria-hidden="true">✓</span>
                                <span>Document root: @if ($webRootPath !== '')<code class="ssltls-code-clip">{{ $webRootPath }}</code>@else<em>not set</em> (set on the hosting account)@endif — certbot will place the ACME response under <code>/.well-known/acme-challenge/</code> here.</span>
                            </li>
                            <li>
                                <span class="ssltls-req__ic" aria-hidden="true">✓</span>
                                <span>Port 80 (HTTP) serves that path before redirecting to HTTPS; the bundled Nginx SSL script does this for new installs.</span>
                            </li>
                            <li>
                                <span class="ssltls-req__ic" aria-hidden="true">✓</span>
                                <span>Install script <code>install-xenweet-certbot-sudo.sh</code> (run as root) grants <code>certbot</code> and a small PEM read helper; both need passwordless <code>sudo</code> for the PHP user so the panel can read root-only files under <code>/etc/letsencrypt/live/</code>. If you see a password error, run <code>sudo bash scripts/install-xenweet-certbot-sudo.sh www-data</code> (use your FPM user if not <code>www-data</code>).</span>
                            </li>
                            <li>
                                <span class="ssltls-req__ic" aria-hidden="true">✓</span>
                                <span>After certbot finishes, the panel runs the <strong>same Nginx install as &ldquo;Install certificate&rdquo;</strong>: HTTPS vhost for this host&rsquo;s <code>php_version</code> (PHP-FPM for the <strong>website</strong> only). The <code>sudo</code> helper does not see <code>PHP_FPM_SOCKET</code> in the environment, so the app passes the socket as a <strong>5th argument</strong> &mdash; keep <code>/usr/local/sbin/xenweet-nginx-install-ssl</code> in sync: <code>sudo bash scripts/install-xenweet-nginx-sudo.sh www-data</code> (use your FPM user if not <code>www-data</code>). If you still get 502 on HTTPS after an upgrade, run that and click <strong>Request &amp; install certificate</strong> again.</span>
                            </li>
                        </ul>
                    </div>

                    <form class="ssltls-auto-form" id="ssltls-le-form" method="POST" action="{{ route('hosts.ssl-tls.lets-encrypt', $hosting) }}">
                        @csrf
                        <div class="managedb-actions ssltls-auto-actions">
                            <button type="submit" class="btn-primary ssltls-btn-primary" id="ssltls-le-submit">
                                <i class="fa fa-lock" aria-hidden="true"></i>
                                Request &amp; install certificate
                            </button>
                        </div>
                    </form>

                    <p class="ssltls-auto-foot subtle">
                        Renewals: run <code>php artisan ssltls:letsencrypt-renew</code> (cron) or <code>SSLTLS_LETSENCRYPT_RENEW_SCHEDULE=true</code> with Laravel&rsquo;s scheduler.
                    </p>
                </div>
            @else
                <div class="ssltls-off" role="note">
                    <p class="ssltls-off__title">Auto SSL is turned off in configuration</p>
                    <p class="ssltls-off__body subtle">Set <code>SSLTLS_LETSENCRYPT_ENABLED=true</code> and a valid <code>SSLTLS_LETSENCRYPT_EMAIL=you@example.com</code> in the panel&rsquo;s <code>.env</code>, then restart PHP or clear config cache (<code>php artisan config:clear</code>).</p>
                </div>
            @endif
        </section>
    @else
        <section class="server-card managedb-card ssltls-tool" aria-labelledby="ssltls-panel-cert-h" role="region">
            <div class="managedb-card__head">
                <h2 id="ssltls-panel-cert-h"><i class="fa fa-certificate" aria-hidden="true"></i> SSL certificate</h2>
                <p>Install the certificate (and any chain) returned by a CA, or create a self-signed cert for local testing. Browsers need a publicly trusted cert for production.</p>
            </div>
            <div class="ssltls-cert-cta">
                @if ($letsEncryptEnabled ?? false)
                    <p class="ssltls-cert-cta__line subtle">
                        Want a free certificate from Let&rsquo;s Encrypt?
                        <a href="{{ route('hosts.ssl-tls', $hosting) }}?tab=auto" class="ssltls-auto__link">Open Auto SSL</a>
                    </p>
                @else
                    <p class="ssltls-cert-cta__line subtle" role="note">
                        For automatic Let&rsquo;s Encrypt on the server, enable <code>SSLTLS_LETSENCRYPT_ENABLED</code> in <code>.env</code> and use the <strong>Auto SSL</strong> tab.
                    </p>
                @endif
            </div>
            <p class="ssltls-manual-eyebrow" id="ssltls-manual-h">Upload manually</p>
            <form class="managedb-form" method="POST" action="{{ route('hosts.ssl-tls.certificate', $hosting) }}" aria-describedby="ssltls-manual-h">
                @csrf
                <label for="ssltls-leaf-cert">PEM (leaf / server certificate)</label>
                <div class="ssltls-pem-frame">
                    <textarea
                        id="ssltls-leaf-cert"
                        class="ssltls-textarea ssltls-textarea--pem"
                        name="certificate_pem"
                        rows="8"
                        spellcheck="false"
                        placeholder="-----BEGIN CERTIFICATE-----&#10;...&#10;-----END CERTIFICATE-----"
                    >{{ old('certificate_pem', $sslStore?->certificate_pem) }}</textarea>
                </div>
                <label for="ssltls-cert-chain">PEM (intermediate chain &mdash; optional, append multiple certs)</label>
                <div class="ssltls-pem-frame">
                    <textarea
                        id="ssltls-cert-chain"
                        class="ssltls-textarea ssltls-textarea--pem"
                        name="certificate_chain_pem"
                        rows="6"
                        spellcheck="false"
                        placeholder="-----BEGIN CERTIFICATE-----&#10;... (intermediate CA)&#10;-----END CERTIFICATE-----"
                    >{{ old('certificate_chain_pem', $sslStore?->certificate_chain_pem) }}</textarea>
                </div>
                <p class="ssltls-hint">Certificates are stored in plain text in the database; restrict access to this panel. Use <strong>Install certificate</strong> below to write PEM files, update nginx to listen on HTTPS, and reload nginx.</p>
                <div class="managedb-actions">
                    <button type="submit" class="btn-primary">Save certificate</button>
                </div>
            </form>
            <form class="managedb-form" method="POST" action="{{ route('hosts.ssl-tls.certificate.install', $hosting) }}">
                @csrf
                <p class="ssltls-hint">Install writes PEM files from saved SSL data to <code>{{ rtrim((string) $hosting->host_root_path, DIRECTORY_SEPARATOR) }}/ssl</code>.</p>
                <div class="managedb-actions">
                    <button type="submit" class="btn-secondary">Install certificate</button>
                </div>
            </form>
        </section>
    @endif

    <div class="ssltls-grid" aria-label="HTTPS and launch checklist">
        <section class="server-card ssltls-panel" aria-labelledby="ssltls-https-h">
            <h2 id="ssltls-https-h">HTTPS on your web server</h2>
            <p class="subtle">
                Uploading a certificate to the panel is only part of the job. The web server must <strong>listen on port 443</strong> with the correct key
                and certificate, and you usually <strong>redirect HTTP to HTTPS</strong> and set security headers.
            </p>
            <ul class="ssltls-stat-list" role="list">
                <li>
                    <span class="ssltls-stat-list__k">TLS / HTTP</span>
                    <span class="ssltls-stat-list__v">vhost SNI, valid chain, <span class="ssltls-badge">HSTS</span> optional</span>
                </li>
                <li>
                    <span class="ssltls-stat-list__k">Quick check</span>
                    <span class="ssltls-stat-list__v"><a href="{{ $httpsUrl }}" class="ssltls-link" target="_blank" rel="noopener noreferrer">Open {{ $hosting->siteHost() }} over HTTPS</a></span>
                </li>
            </ul>
        </section>

        <section class="server-card ssltls-panel" aria-labelledby="ssltls-checklist-h">
            <h2 id="ssltls-checklist-h">Before you go live</h2>
            <ul class="ssltls-checklist">
                <li>Private key generated and <strong>never</strong> leaves the host except in a secure backup.</li>
                <li>CSR sent to a CA; issued certificate and intermediate chain <strong>installed in order</strong>.</li>
                <li>Web server config references the correct <span class="ssltls-code-inline">SSLCertificateFile</span> and key paths (or panel equivalents).</li>
                <li>HTTP&rarr;HTTPS redirect and, when ready, <span class="ssltls-code-inline">Strict-Transport-Security</span> with an appropriate <code>max-age</code>.</li>
            </ul>
        </section>
    </div>
</div>

<script>
    (function () {
        var btn = document.getElementById('ssltls-copy-csr-btn');
        var area = document.getElementById('ssltls-csr-out');
        if (!btn || !area) {
            return;
        }
        btn.addEventListener('click', async function () {
            var value = (area.value || '').trim();
            if (!value) {
                btn.textContent = 'No CSR yet';
                setTimeout(function () { btn.textContent = 'Copy CSR'; }, 1400);
                return;
            }
            try {
                await navigator.clipboard.writeText(value + '\n');
                btn.textContent = 'Copied';
            } catch (e) {
                area.focus();
                area.select();
                btn.textContent = 'Select and copy';
            }
            setTimeout(function () { btn.textContent = 'Copy CSR'; }, 1400);
        });
    })();
</script>
@if ($activeToolTab === 'auto')
    <script>
        (function () {
            var form = document.getElementById('ssltls-le-form');
            var btn = document.getElementById('ssltls-le-submit');
            if (!form || !btn) { return; }
            form.addEventListener('submit', function () {
                if (btn.getAttribute('aria-disabled') === 'true') { return; }
                btn.setAttribute('aria-disabled', 'true');
                btn.disabled = true;
                var label = (btn.getAttribute('data-label-busy') || 'Requesting…');
                if (!btn.getAttribute('data-label-restore')) {
                    btn.setAttribute('data-label-restore', btn.textContent.replace(/\s+/g, ' ').trim());
                }
                var ic = '<i class="fa fa-lock" aria-hidden="true"></i> ';
                btn.innerHTML = ic + label;
            });
        })();
    </script>
@endif
@endsection
