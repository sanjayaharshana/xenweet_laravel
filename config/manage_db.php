<?php

return [
    'host' => env('MANAGE_DB_HOST', env('DB_HOST', '127.0.0.1')),
    'port' => env('MANAGE_DB_PORT', env('DB_PORT', 3306)),
    'admin_user' => env('MANAGE_DB_ADMIN_USER', env('DB_USERNAME', 'root')),
    'admin_password' => env('MANAGE_DB_ADMIN_PASSWORD', env('DB_PASSWORD', '')),

    /**
     * Optional: full URL to an Adminer instance. When empty, the app uses public/adminer.php
     * (https://{APP_URL}/adminer.php). Set this to use Adminer on another host.
     */
    'adminer_url' => env('MANAGE_DB_ADMINER_URL', ''),
];
