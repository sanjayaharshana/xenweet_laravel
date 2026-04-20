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
    | Remove Command (on delete)
    |--------------------------------------------------------------------------
    |
    | When a hosting row is deleted from the panel, this shell command runs first
    | with the same placeholders as "command" above.
    |
    */
    'remove_enabled' => (bool) env('HOSTING_SPLIT_REMOVE_ENABLED', true),

    'remove_command' => env(
        'HOSTING_SPLIT_REMOVE_COMMAND',
        "echo '[split-server] unbind {domain} from {web_root_path} on {server_ip} user={panel_username}'"
    ),

    /*
    |--------------------------------------------------------------------------
    | Web server vhost snippet (after folders + paths exist)
    |--------------------------------------------------------------------------
    |
    | When enabled, runs after host_root_path / web_root_path are saved.
    | Scripts write to storage/app/hosting-vhosts/ — copy into nginx/apache
    | with sudo (see script header comments). Set HOSTING_VHOST_ENABLED=true
    | on the server. Use hosting-vhost-apache.sh if you use Apache.
    |
    */
    'vhost_enabled' => (bool) env('HOSTING_VHOST_ENABLED', false),

    'vhost_script' => env('HOSTING_VHOST_SCRIPT', base_path('scripts/hosting-vhost-nginx.sh')),

    'vhost_remove_script' => env('HOSTING_VHOST_REMOVE_SCRIPT', base_path('scripts/hosting-vhost-remove.sh')),

    'vhost_stop_on_error' => (bool) env('HOSTING_VHOST_STOP_ON_ERROR', true),

    /*
    |--------------------------------------------------------------------------
    | PHP-FPM socket for generated nginx vhost (override per server layout)
    |--------------------------------------------------------------------------
    */
    'php_fpm_socket' => env('HOSTING_PHP_FPM_SOCKET'),

    /*
    |--------------------------------------------------------------------------
    | Nginx: copy snippet into sites-available / sites-enabled and reload
    |--------------------------------------------------------------------------
    |
    | Runs after the nginx vhost file is written to storage/app/hosting-vhosts/.
    | Requires passwordless sudo for the PHP user — see scripts/hosting-vhost-nginx-activate.sh
    |
    */
    'vhost_nginx_activate' => (bool) env('HOSTING_VHOST_NGINX_ACTIVATE', false),

    'vhost_nginx_activate_script' => env(
        'HOSTING_VHOST_NGINX_ACTIVATE_SCRIPT',
        base_path('scripts/hosting-vhost-nginx-activate.sh')
    ),

    'vhost_nginx_deactivate_script' => env(
        'HOSTING_VHOST_NGINX_DEACTIVATE_SCRIPT',
        base_path('scripts/hosting-vhost-nginx-deactivate.sh')
    ),

    /*
    | Optional: paths to root helpers installed by scripts/install-xenweet-nginx-sudo.sh
    | When executable, Laravel uses sudo -n <path> (single NOPASSWD rule) instead of
    | the bash wrappers that call sudo many times.
    */
    'vhost_nginx_system_activate' => env('HOSTING_VHOST_NGINX_SYSTEM_BIN'),

    'vhost_nginx_system_deactivate' => env('HOSTING_VHOST_NGINX_SYSTEM_DEACTIVATE_BIN'),

    /*
    |--------------------------------------------------------------------------
    | Command Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('HOSTING_SPLIT_TIMEOUT', 120),
];
