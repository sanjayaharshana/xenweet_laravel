<?php

return [
    'tabs' => [
        'general' => [
            'label' => 'General',
            'icon' => 'fa fa-sliders',
            'fields' => [
                ['key' => 'panel_name', 'label' => 'Panel Name', 'type' => 'text', 'default' => 'Xenweet Panel'],
                ['key' => 'timezone', 'label' => 'Timezone', 'type' => 'select', 'default' => 'UTC', 'options' => ['UTC', 'Asia/Colombo', 'Asia/Kolkata', 'Europe/London', 'America/New_York']],
                ['key' => 'items_per_page', 'label' => 'Items Per Page', 'type' => 'number', 'default' => 25, 'min' => 5, 'max' => 200],
            ],
        ],
        'security' => [
            'label' => 'Security',
            'icon' => 'fa fa-shield',
            'fields' => [
                ['key' => 'force_https', 'label' => 'Force HTTPS', 'type' => 'boolean', 'default' => true],
                ['key' => 'session_timeout', 'label' => 'Session Timeout (minutes)', 'type' => 'number', 'default' => 120, 'min' => 5, 'max' => 1440],
                ['key' => 'allow_ip_login', 'label' => 'Allow IP Login Access', 'type' => 'boolean', 'default' => false],
            ],
        ],
        'notifications' => [
            'label' => 'Notifications',
            'icon' => 'fa fa-bell',
            'fields' => [
                ['key' => 'email_alerts', 'label' => 'Email Alerts', 'type' => 'boolean', 'default' => true],
                ['key' => 'weekly_report', 'label' => 'Weekly Usage Report', 'type' => 'boolean', 'default' => true],
                ['key' => 'alert_email', 'label' => 'Alert Email', 'type' => 'text', 'default' => 'admin@example.com'],
            ],
        ],
        'db_management' => [
            'label' => 'DB Management',
            'icon' => 'fa fa-database',
            'fields' => [
                ['key' => 'postgres_enabled', 'label' => 'Enable PostgreSQL', 'type' => 'boolean', 'default' => false],
                ['key' => 'postgres_host', 'label' => 'PostgreSQL Host', 'type' => 'text', 'default' => '127.0.0.1', 'depends_on' => 'postgres_enabled'],
                ['key' => 'postgres_port', 'label' => 'PostgreSQL Port', 'type' => 'number', 'default' => 5432, 'depends_on' => 'postgres_enabled'],
                ['key' => 'postgres_database', 'label' => 'PostgreSQL Database', 'type' => 'text', 'default' => 'postgres', 'depends_on' => 'postgres_enabled'],
                ['key' => 'postgres_username', 'label' => 'PostgreSQL Root User', 'type' => 'text', 'default' => 'postgres', 'depends_on' => 'postgres_enabled'],
                ['key' => 'postgres_password', 'label' => 'PostgreSQL Root Password', 'type' => 'password', 'default' => '', 'depends_on' => 'postgres_enabled'],

                ['key' => 'mysql_enabled', 'label' => 'Enable MySQL', 'type' => 'boolean', 'default' => true],
                ['key' => 'mysql_host', 'label' => 'MySQL Host', 'type' => 'text', 'default' => '127.0.0.1', 'depends_on' => 'mysql_enabled'],
                ['key' => 'mysql_port', 'label' => 'MySQL Port', 'type' => 'number', 'default' => 3306, 'depends_on' => 'mysql_enabled'],
                ['key' => 'mysql_database', 'label' => 'MySQL Database', 'type' => 'text', 'default' => 'mysql', 'depends_on' => 'mysql_enabled'],
                ['key' => 'mysql_username', 'label' => 'MySQL Root User', 'type' => 'text', 'default' => 'root', 'depends_on' => 'mysql_enabled'],
                ['key' => 'mysql_password', 'label' => 'MySQL Root Password', 'type' => 'password', 'default' => '', 'depends_on' => 'mysql_enabled'],

                ['key' => 'sqlite_enabled', 'label' => 'Enable SQLite', 'type' => 'boolean', 'default' => false],
                ['key' => 'sqlite_host', 'label' => 'SQLite Host', 'type' => 'text', 'default' => 'localhost', 'depends_on' => 'sqlite_enabled'],
                ['key' => 'sqlite_port', 'label' => 'SQLite Port', 'type' => 'number', 'default' => 0, 'depends_on' => 'sqlite_enabled'],
                ['key' => 'sqlite_database', 'label' => 'SQLite File Path', 'type' => 'text', 'default' => database_path('database.sqlite'), 'depends_on' => 'sqlite_enabled'],
                ['key' => 'sqlite_username', 'label' => 'SQLite Root User', 'type' => 'text', 'default' => 'root', 'depends_on' => 'sqlite_enabled'],
                ['key' => 'sqlite_password', 'label' => 'SQLite Root Password', 'type' => 'password', 'default' => '', 'depends_on' => 'sqlite_enabled'],

                ['key' => 'central_db_enabled', 'label' => 'Enable Central DB', 'type' => 'boolean', 'default' => false],
                ['key' => 'central_db_host', 'label' => 'Central DB Host', 'type' => 'text', 'default' => '127.0.0.1', 'depends_on' => 'central_db_enabled'],
                ['key' => 'central_db_port', 'label' => 'Central DB Port', 'type' => 'number', 'default' => 3306, 'depends_on' => 'central_db_enabled'],
                ['key' => 'central_db_database', 'label' => 'Central DB Name', 'type' => 'text', 'default' => 'xenweet_central', 'depends_on' => 'central_db_enabled'],
                ['key' => 'central_db_username', 'label' => 'Central DB Root User', 'type' => 'text', 'default' => 'root', 'depends_on' => 'central_db_enabled'],
                ['key' => 'central_db_password', 'label' => 'Central DB Root Password', 'type' => 'password', 'default' => '', 'depends_on' => 'central_db_enabled'],
            ],
        ],
    ],
];
