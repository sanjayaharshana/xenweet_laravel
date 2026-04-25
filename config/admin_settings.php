<?php

return [
    'tabs' => [
        'general' => [
            'label' => 'General',
            'icon' => 'fa fa-sliders',
            'fields' => [
                ['key' => 'panel_name', 'label' => 'Panel Name', 'type' => 'text', 'default' => 'Xenweet Panel', 'description' => 'Display name shown across the admin panel.'],
                ['key' => 'timezone', 'label' => 'Timezone', 'type' => 'select', 'default' => 'UTC', 'options' => ['UTC', 'Asia/Colombo', 'Asia/Kolkata', 'Europe/London', 'America/New_York'], 'description' => 'Default timezone used for panel dates and times.'],
                ['key' => 'items_per_page', 'label' => 'Items Per Page', 'type' => 'number', 'default' => 25, 'min' => 5, 'max' => 200, 'description' => 'Controls default list pagination size in panel tables.'],
            ],
        ],
        'security' => [
            'label' => 'Security',
            'icon' => 'fa fa-shield',
            'fields' => [
                ['key' => 'force_https', 'label' => 'Force HTTPS', 'type' => 'boolean', 'default' => true, 'description' => 'Redirect users to secure HTTPS URLs whenever possible.'],
                ['key' => 'session_timeout', 'label' => 'Session Timeout (minutes)', 'type' => 'number', 'default' => 120, 'min' => 5, 'max' => 1440, 'description' => 'Automatically log out inactive users after this period.'],
                ['key' => 'allow_ip_login', 'label' => 'Allow IP Login Access', 'type' => 'boolean', 'default' => false, 'description' => 'Permit login using server IP instead of domain name.'],
            ],
        ],
        'notifications' => [
            'label' => 'Notifications',
            'icon' => 'fa fa-bell',
            'fields' => [
                ['key' => 'email_alerts', 'label' => 'Email Alerts', 'type' => 'boolean', 'default' => true, 'description' => 'Send panel event alerts to the configured email address.'],
                ['key' => 'weekly_report', 'label' => 'Weekly Usage Report', 'type' => 'boolean', 'default' => true, 'description' => 'Send weekly summary reports about usage and activity.'],
                ['key' => 'alert_email', 'label' => 'Alert Email', 'type' => 'text', 'default' => 'admin@example.com', 'description' => 'Primary recipient for system alert emails.'],
            ],
        ],
        'mail_settings' => [
            'label' => 'Mail Settings',
            'icon' => 'fa fa-envelope',
            'fields' => [
                ['key' => 'mail_enabled', 'label' => 'Enable Mail', 'type' => 'boolean', 'default' => true, 'description' => 'Master switch for outgoing email features.'],
                ['key' => 'mail_mailer', 'label' => 'Mailer', 'type' => 'select', 'default' => env('MAIL_MAILER', 'smtp'), 'options' => ['smtp', 'sendmail', 'log', 'array'], 'description' => 'Transport driver used to send mail messages.'],
                ['key' => 'mail_host', 'label' => 'SMTP Host', 'type' => 'text', 'default' => env('MAIL_HOST', '127.0.0.1'), 'depends_on' => 'mail_enabled', 'description' => 'Hostname or IP address of your SMTP server.'],
                ['key' => 'mail_port', 'label' => 'SMTP Port', 'type' => 'number', 'default' => (int) env('MAIL_PORT', 587), 'depends_on' => 'mail_enabled', 'description' => 'Port used by the SMTP server (common: 587 or 465).'],
                ['key' => 'mail_username', 'label' => 'SMTP Username', 'type' => 'text', 'default' => env('MAIL_USERNAME', ''), 'depends_on' => 'mail_enabled', 'description' => 'Login username for SMTP authentication.'],
                ['key' => 'mail_password', 'label' => 'SMTP Password', 'type' => 'password', 'default' => env('MAIL_PASSWORD', ''), 'depends_on' => 'mail_enabled', 'description' => 'Login password or app password for SMTP access.'],
                ['key' => 'mail_encryption', 'label' => 'Encryption', 'type' => 'select', 'default' => env('MAIL_ENCRYPTION', 'tls'), 'options' => ['', 'tls', 'ssl'], 'depends_on' => 'mail_enabled', 'description' => 'Connection encryption mode for SMTP traffic.'],
                ['key' => 'mail_from_address', 'label' => 'From Address', 'type' => 'text', 'default' => env('MAIL_FROM_ADDRESS', 'hello@example.com'), 'depends_on' => 'mail_enabled', 'description' => 'Default sender email address for outgoing mail.'],
                ['key' => 'mail_from_name', 'label' => 'From Name', 'type' => 'text', 'default' => env('MAIL_FROM_NAME', env('APP_NAME', 'Xenweet')), 'depends_on' => 'mail_enabled', 'description' => 'Default sender display name for outgoing mail.'],
            ],
        ],
        'db_management' => [
            'label' => 'DB Management',
            'icon' => 'fa fa-database',
            'help' => [
                'summary' => 'Use these values for the panel to test and use database connections (hosting tools, health checks, etc.). They are not the same as your Laravel .env file unless you choose to match them. Enter a user that can create databases and users on the target server when the panel needs that access.',
                'items' => [
                    [
                        'title' => 'MySQL: password and host',
                        'body' => 'MySQL always authenticates a user. If the mysql client works without typing a password, the password is usually in ~/.my.cnf. Host 127.0.0.1 and localhost are not always the same in MySQL (socket vs TCP); use the one that matches your server.',
                    ],
                    [
                        'title' => 'Optional: new MySQL user with full privileges',
                        'body' => 'Run the following in a MySQL client while connected as a privileged user (e.g. existing root). Change the user name, host, and password to suit your environment.',
                        'code' => <<<'SQL'
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'YourStrongPasswordHere';
GRANT ALL PRIVILEGES ON *.* TO 'admin'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SQL
                    ],
                ],
            ],
            'fields' => [
                ['key' => 'postgres_enabled', 'label' => 'Enable PostgreSQL', 'type' => 'boolean', 'default' => false, 'description' => 'Enable PostgreSQL management tools in the panel.'],
                ['key' => 'postgres_host', 'label' => 'PostgreSQL Host', 'type' => 'text', 'default' => '127.0.0.1', 'depends_on' => 'postgres_enabled', 'description' => 'Server host used for PostgreSQL admin connections.'],
                ['key' => 'postgres_port', 'label' => 'PostgreSQL Port', 'type' => 'number', 'default' => 5432, 'depends_on' => 'postgres_enabled', 'description' => 'Server port used for PostgreSQL admin connections.'],
                ['key' => 'postgres_database', 'label' => 'PostgreSQL Database', 'type' => 'text', 'default' => 'postgres', 'depends_on' => 'postgres_enabled', 'description' => 'Default PostgreSQL database for connection tests.'],
                ['key' => 'postgres_username', 'label' => 'PostgreSQL Root User', 'type' => 'text', 'default' => 'postgres', 'depends_on' => 'postgres_enabled', 'description' => 'Administrative PostgreSQL username used by tools.'],
                ['key' => 'postgres_password', 'label' => 'PostgreSQL Root Password', 'type' => 'password', 'default' => '', 'depends_on' => 'postgres_enabled', 'description' => 'Administrative PostgreSQL password used by tools.'],

                ['key' => 'mysql_enabled', 'label' => 'Enable MySQL', 'type' => 'boolean', 'default' => true, 'description' => 'Enable MySQL management tools in the panel.'],
                ['key' => 'mysql_host', 'label' => 'MySQL Host', 'type' => 'text', 'default' => '127.0.0.1', 'depends_on' => 'mysql_enabled', 'description' => 'Server host used for MySQL admin connections.'],
                ['key' => 'mysql_port', 'label' => 'MySQL Port', 'type' => 'number', 'default' => 3306, 'depends_on' => 'mysql_enabled', 'description' => 'Server port used for MySQL admin connections.'],
                ['key' => 'mysql_database', 'label' => 'MySQL Database', 'type' => 'text', 'default' => 'mysql', 'depends_on' => 'mysql_enabled', 'description' => 'Default MySQL database for connection tests.'],
                ['key' => 'mysql_username', 'label' => 'MySQL Root User', 'type' => 'text', 'default' => 'root', 'depends_on' => 'mysql_enabled', 'description' => 'Administrative MySQL username used by tools.'],
                ['key' => 'mysql_password', 'label' => 'MySQL Root Password', 'type' => 'password', 'default' => '', 'depends_on' => 'mysql_enabled', 'description' => 'Administrative MySQL password used by tools.'],

                ['key' => 'sqlite_enabled', 'label' => 'Enable SQLite', 'type' => 'boolean', 'default' => false, 'description' => 'Enable SQLite utilities in the panel.'],
                ['key' => 'sqlite_host', 'label' => 'SQLite Host', 'type' => 'text', 'default' => 'localhost', 'depends_on' => 'sqlite_enabled', 'description' => 'Display-only host label for SQLite profile settings.'],
                ['key' => 'sqlite_port', 'label' => 'SQLite Port', 'type' => 'number', 'default' => 0, 'depends_on' => 'sqlite_enabled', 'description' => 'Display-only port field for SQLite profile settings.'],
                ['key' => 'sqlite_database', 'label' => 'SQLite File Path', 'type' => 'text', 'default' => database_path('database.sqlite'), 'depends_on' => 'sqlite_enabled', 'description' => 'Absolute path to the SQLite database file.'],
                ['key' => 'sqlite_username', 'label' => 'SQLite Root User', 'type' => 'text', 'default' => 'root', 'depends_on' => 'sqlite_enabled', 'description' => 'Display-only username label for SQLite profile settings.'],
                ['key' => 'sqlite_password', 'label' => 'SQLite Root Password', 'type' => 'password', 'default' => '', 'depends_on' => 'sqlite_enabled', 'description' => 'Display-only password field for SQLite profile settings.'],

                ['key' => 'central_db_enabled', 'label' => 'Enable Central DB', 'type' => 'boolean', 'default' => false, 'description' => 'Enable centralized database profile features.'],
                ['key' => 'central_db_host', 'label' => 'Central DB Host', 'type' => 'text', 'default' => '127.0.0.1', 'depends_on' => 'central_db_enabled', 'description' => 'Host for the shared central database server.'],
                ['key' => 'central_db_port', 'label' => 'Central DB Port', 'type' => 'number', 'default' => 3306, 'depends_on' => 'central_db_enabled', 'description' => 'Port for the shared central database server.'],
                ['key' => 'central_db_database', 'label' => 'Central DB Name', 'type' => 'text', 'default' => 'xenweet_central', 'depends_on' => 'central_db_enabled', 'description' => 'Database name used by shared platform services.'],
                ['key' => 'central_db_username', 'label' => 'Central DB Root User', 'type' => 'text', 'default' => 'root', 'depends_on' => 'central_db_enabled', 'description' => 'Administrative username for the central database.'],
                ['key' => 'central_db_password', 'label' => 'Central DB Root Password', 'type' => 'password', 'default' => '', 'depends_on' => 'central_db_enabled', 'description' => 'Administrative password for the central database.'],
            ],
        ],
    ],
];
