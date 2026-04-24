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

];
