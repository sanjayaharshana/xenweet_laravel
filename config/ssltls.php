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

];
