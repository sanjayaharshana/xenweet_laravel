<?php

$choices = (string) env('PHP_VERSION_CHOICES', '8.1,8.2,8.3,8.4');
$parsed = array_filter(array_map('trim', explode(',', $choices)));
if ($parsed === []) {
    $parsed = ['8.1', '8.2', '8.3', '8.4'];
}

return [

    /*
    |--------------------------------------------------------------------------
    | Versions offered in the host panel
    |--------------------------------------------------------------------------
    |
    | Comma-separated list, e.g. 8.1,8.2,8.3,8.4. Each value must match an
    | installed phpX.Y-fpm socket on the server, e.g.:
    | /var/run/php/php8.3-fpm.sock
    |
    | Web only: this controls which pool Nginx fastcgi uses for the site, not
    | the CLI PHP in SSH.
    |
    */
    'available_versions' => array_values($parsed),
];
