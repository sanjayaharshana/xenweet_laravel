<?php

return [
    'host' => env('MANAGE_DB_HOST', env('DB_HOST', '127.0.0.1')),
    'port' => env('MANAGE_DB_PORT', env('DB_PORT', 3306)),
    'admin_user' => env('MANAGE_DB_ADMIN_USER', env('DB_USERNAME', 'root')),
    'admin_password' => env('MANAGE_DB_ADMIN_PASSWORD', env('DB_PASSWORD', '')),
];
