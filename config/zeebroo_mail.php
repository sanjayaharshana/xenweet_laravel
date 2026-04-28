<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Disable live mail-server connections
    |--------------------------------------------------------------------------
    |
    | Useful for local development where IMAP/SMTP services are unavailable.
    | When enabled, ZeeBroo Mail will not attempt IMAP or SMTP network calls.
    |
    */
    'disable_connections' => (bool) env('ZEEBROO_MAIL_DISABLE_CONNECTIONS', false),
];

