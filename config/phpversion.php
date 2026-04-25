<?php

$choices = (string) env('PHP_VERSION_CHOICES', '8.1,8.2,8.3,8.4');
$parsed = array_filter(array_map('trim', explode(',', $choices)));
if ($parsed === []) {
    $parsed = ['8.1', '8.2', '8.3', '8.4'];
}

$extensions = (string) env('PHP_EXTENSION_CHOICES', 'bcmath,ctype,curl,dom,exif,fileinfo,gd,imagick,intl,mbstring,mysqli,opcache,pdo,pdo_mysql,redis,simplexml,soap,xml,xmlreader,xmlwriter,zip');
$parsedExtensions = array_values(array_unique(array_filter(array_map('trim', explode(',', $extensions)))));
if ($parsedExtensions === []) {
    $parsedExtensions = ['bcmath', 'curl', 'gd', 'imagick', 'intl', 'mbstring', 'mysqli', 'opcache', 'pdo_mysql', 'redis', 'zip'];
}

$iniOptions = [
    'allow_url_fopen' => [
        'label' => 'allow_url_fopen',
        'type' => 'boolean',
        'default' => 'On',
        'help' => 'Allow URL-aware fopen wrappers (http/ftp) for file functions.',
    ],
    'max_input_time' => [
        'label' => 'max_input_time',
        'type' => 'number',
        'default' => '60',
        'help' => 'Maximum time in seconds a script is allowed to parse input data.',
    ],
    'max_execution_time' => [
        'label' => 'max_execution_time',
        'type' => 'number',
        'default' => '30',
        'help' => 'Maximum execution time of each script, in seconds.',
    ],
    'memory_limit' => [
        'label' => 'memory_limit',
        'type' => 'text',
        'default' => '256M',
        'help' => 'Maximum amount of memory a script may consume.',
    ],
    'post_max_size' => [
        'label' => 'post_max_size',
        'type' => 'text',
        'default' => '64M',
        'help' => 'Maximum size of POST data accepted by PHP.',
    ],
    'upload_max_filesize' => [
        'label' => 'upload_max_filesize',
        'type' => 'text',
        'default' => '64M',
        'help' => 'Maximum uploaded file size.',
    ],
    'max_input_vars' => [
        'label' => 'max_input_vars',
        'type' => 'number',
        'default' => '1000',
        'help' => 'How many input variables may be accepted.',
    ],
    'display_errors' => [
        'label' => 'display_errors',
        'type' => 'boolean',
        'default' => 'Off',
        'help' => 'Whether errors should be printed to output.',
    ],
    'error_reporting' => [
        'label' => 'error_reporting',
        'type' => 'text',
        'default' => 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
        'help' => 'Bitmask/value that defines which PHP errors are reported.',
    ],
];

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

    /*
    |--------------------------------------------------------------------------
    | Extensions offered in the host panel
    |--------------------------------------------------------------------------
    |
    | This list powers the PHP Extensions tab toggle UI. By default this
    | stores host-specific enabled/disabled preferences in DB.
    |
    */
    'available_extensions' => $parsedExtensions,

    /*
    |--------------------------------------------------------------------------
    | Editable php.ini options in host panel
    |--------------------------------------------------------------------------
    */
    'ini_options' => $iniOptions,
];
