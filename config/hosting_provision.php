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
    | Base Directory For Hosted Domains
    |--------------------------------------------------------------------------
    |
    | Each domain will be created under this root path, for example:
    | /path/to/root/example.com/public_html
    |
    */
    'hosts_root' => env('HOSTING_SITES_ROOT', storage_path('app/hosting-sites')),

    /*
    |--------------------------------------------------------------------------
    | Command Template
    |--------------------------------------------------------------------------
    |
    | Available placeholders:
    | {id}, {domain}, {server_ip}, {plan}, {panel_username},
    | {panel_password}, {php_version}, {status}, {disk_usage_mb},
    | {host_root_path}, {web_root_path}
    |
    */
    'command' => env(
        'HOSTING_SPLIT_COMMAND',
        "echo '[split-server] bind {domain} to {web_root_path} on {server_ip} plan={plan} user={panel_username}'"
    ),

    /*
    |--------------------------------------------------------------------------
    | Command Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('HOSTING_SPLIT_TIMEOUT', 120),
];
