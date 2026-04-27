<?php

$config = [];
$projectRoot = dirname(__DIR__, 3);
$roundcubeDb = $projectRoot.'/storage/app/roundcube-central/roundcube.sqlite';

// 1) Explicit Roundcube DSN override from environment (recommended in production)
$explicitDsn = getenv('ROUNDCUBE_DB_DSNW');
if (is_string($explicitDsn) && trim($explicitDsn) !== '') {
    $config['db_dsnw'] = trim($explicitDsn);
} else {
    // 2) If sqlite PDO is available, use project-local sqlite database
    if (extension_loaded('pdo_sqlite')) {
        $config['db_dsnw'] = 'sqlite:'.$roundcubeDb;
    } else {
        // 3) Fallback to MySQL using app DB env variables
        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbPort = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('ROUNDCUBE_DB_NAME') ?: 'roundcube';
        $dbUser = getenv('ROUNDCUBE_DB_USER') ?: (getenv('DB_USERNAME') ?: 'root');
        $dbPass = getenv('ROUNDCUBE_DB_PASS') ?: (getenv('DB_PASSWORD') ?: '');

        $config['db_dsnw'] = sprintf(
            'mysql://%s:%s@%s:%s/%s?charset=utf8mb4',
            rawurlencode((string) $dbUser),
            rawurlencode((string) $dbPass),
            (string) $dbHost,
            (string) $dbPort,
            (string) $dbName
        );
    }
}
// IMAP: match Dovecot on this host. Use tls://127.0.0.1 if Dovecot requires STARTTLS on 143,
// or ssl://127.0.0.1 with ROUNDCUBE_DEFAULT_PORT=993 for implicit TLS.
$config['default_host'] = getenv('ROUNDCUBE_DEFAULT_HOST') !== false && getenv('ROUNDCUBE_DEFAULT_HOST') !== ''
    ? getenv('ROUNDCUBE_DEFAULT_HOST')
    : 'localhost';
$imapPortEnv = getenv('ROUNDCUBE_DEFAULT_PORT');
$config['default_port'] = ($imapPortEnv !== false && $imapPortEnv !== '')
    ? (int) $imapPortEnv
    : 143;
$config['smtp_server'] = 'localhost';
$config['smtp_port'] = 587;
$config['smtp_user'] = '%u';
$config['smtp_pass'] = '%p';
$config['product_name'] = 'Webmail';
$config['des_key'] = '07dd32479e7b1688710f4149';
$config['base_uri'] = '/roundcube/';
$config['plugins'] = ['archive', 'zipdownload'];
$config['skin'] = 'elastic';
$config['enable_installer'] = false;
$config['temp_dir'] = __DIR__.'/../temp';
$config['log_dir'] = __DIR__.'/../logs';
