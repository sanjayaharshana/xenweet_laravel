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
        </section>
    @else
        <section class="server-card managedb-card ssltls-tool" aria-labelledby="ssltls-panel-cert-h" role="region">
            <div class="managedb-card__head">
                <h2 id="ssltls-panel-cert-h"><i class="fa fa-certificate" aria-hidden="true"></i> SSL certificate</h2>
                <p>Install the certificate (and any chain) returned by a CA, or create a self-signed cert for local testing. Browsers need a publicly trusted cert for production.</p>
            </div>
            <form class="managedb-form" method="POST" action="{{ route('hosts.ssl-tls.certificate', $hosting) }}">
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
                <p class="ssltls-hint">Certificates are stored in plain text in the database; restrict access to this panel. Installing on the web server and reloading the vhost is a separate step.</p>
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
@endsection
