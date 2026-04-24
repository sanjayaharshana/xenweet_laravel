<?php

return [

    /*
    | OpenSSL binary (must be on PATH or an absolute path).
    */
    'openssl_binary' => env('SSLTLS_OPENSSL_BINARY', 'openssl'),

    /*
    | Path to the helper shell script (genkey / gencsr). Override if deployed elsewhere.
    */
    'script_path' => env('SSLTLS_SCRIPT_PATH', base_path('Modules/SslTls/bin/ssltls-openssl.sh')),

    /*
    | Install SSL into Nginx vhost and reload.
    */
    'nginx_ssl_install_script' => env('SSLTLS_NGINX_SSL_INSTALL_SCRIPT', base_path('scripts/hosting-vhost-nginx-install-ssl.sh')),

    /*
    | Optional root helper (preferred): sudo -n <bin> <domain> <web_root> <key_pem> <fullchain_pem>
    */
    'nginx_ssl_system_install_bin' => env('SSLTLS_NGINX_SSL_SYSTEM_INSTALL_BIN'),

    'nginx_ssl_install_timeout' => (int) env('SSLTLS_NGINX_SSL_INSTALL_TIMEOUT', 90),

    /*
    | Let's Encrypt (certbot) — "Auto SSL" for self-hosted Nginx. Disabled by default; enable in production when
    | certbot is installed, sudo allowlists are configured, and the panel can run on the same machine as the site.
    */
    'letsencrypt_enabled' => (bool) env('SSLTLS_LETSENCRYPT_ENABLED', false),

    'letsencrypt_email' => env('SSLTLS_LETSENCRYPT_EMAIL'),

    'letsencrypt_staging' => (bool) env('SSLTLS_LETSENCRYPT_STAGING', false),

    'letsencrypt_certbot' => env('SSLTLS_LETSENCRYPT_CERTBOT', 'certbot'),

    'letsencrypt_use_sudo' => (bool) env('SSLTLS_LETSENCRYPT_USE_SUDO', true),

    'letsencrypt_check_binary' => (bool) env('SSLTLS_LETSENCRYPT_CHECK_BINARY', true),

    'letsencrypt_config_dir' => env('SSLTLS_LETSENCRYPT_CONFIG_DIR', '/etc/letsencrypt'),

    'letsencrypt_work_dir' => env('SSLTLS_LETSENCRYPT_WORK_DIR', '/var/lib/letsencrypt'),

    'letsencrypt_logs_dir' => env('SSLTLS_LETSENCRYPT_LOGS_DIR', '/var/log/letsencrypt'),

    'letsencrypt_pre_hook' => env('SSLTLS_LETSENCRYPT_PRE_HOOK', ''),

    'letsencrypt_timeout' => (int) env('SSLTLS_LETSENCRYPT_TIMEOUT', 300),

    'letsencrypt_renew_timeout' => (int) env('SSLTLS_LETSENCRYPT_RENEW_TIMEOUT', 600),

    'letsencrypt_renew_schedule' => (bool) env('SSLTLS_LETSENCRYPT_RENEW_SCHEDULE', false),
];
