<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hosting CLI Provision Toggle
    |--------------------------------------------------------------------------
    |
    | When enabled, creating a hosting record will execute the command below
    | and replace placeholders with actual values from the hosting record.
    |
    */
    'enabled' => (bool) env('HOSTING_SPLIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Command Template
    |--------------------------------------------------------------------------
    |
    | Available placeholders:
    | {id}, {domain}, {server_ip}, {plan}, {panel_username},
    | {panel_password}, {php_version}, {status}, {disk_usage_mb}
    |
    */
    'command' => env(
        'HOSTING_SPLIT_COMMAND',
        "echo '[split-server] provision {domain} on {server_ip} plan={plan} user={panel_username}'"
    ),

    /*
    |--------------------------------------------------------------------------
    | Command Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('HOSTING_SPLIT_TIMEOUT', 120),
];
