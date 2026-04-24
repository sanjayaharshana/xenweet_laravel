<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Host Panel — application shortcuts (per hosting)
    |--------------------------------------------------------------------------
    |
    | Categories and items shown on /hosts/{hosting}/panel.
    |
    | Per item (optional keys):
    | - route          Laravel route name (parameters merged with ['hosting' => $hosting])
    | - route_parameters  Extra route parameters (array)
    | - url            Absolute or relative URL (used if route is empty or unknown)
    | - target         e.g. _blank
    | - description    Subtitle on the tile
    | - icon           Font Awesome 4 classes, e.g. "fa fa-folder-open"
    |
    | Items with no resolvable route/url render as “coming soon” tiles until you
    | add integration here.
    |
    */
    'categories' => [
        [
            'id' => 'files',
            'title' => 'Files',
            'items' => [
                ['label' => 'File Manager', 'description' => 'Browse files on the server', 'icon' => 'fa fa-folder-open', 'route' => 'hosts.files.index', 'target' => '_blank'],
                ['label' => 'File Backup', 'description' => 'Backups and restore', 'icon' => 'fa fa-cloud-upload'],
                ['label' => 'Git', 'description' => 'Repositories and deploy keys', 'icon' => 'fa fa-code-fork'],
                ['label' => 'Update pipeline', 'description' => 'CI / deployment', 'icon' => 'fa fa-refresh'],
                ['label' => 'Web Disk Usage', 'description' => 'Quota and usage', 'icon' => 'fa fa-pie-chart'],
                ['label' => 'Privacy', 'description' => 'Access and policies', 'icon' => 'fa fa-shield'],
                ['label' => 'Media Management', 'description' => 'Images and assets', 'icon' => 'fa fa-film'],
            ],
        ],
        [
            'id' => 'security',
            'title' => 'Security',
            'items' => [
                [
                    'label' => 'SSL',
                    'description' => 'TLS/SSL certificates, HTTPS, and security headers for this host',
                    'icon' => 'fa fa-lock',
                    'route' => 'hosts.ssl-tls',
                ],
                [
                    'label' => 'SSH Access',
                    'description' => 'Shell access, authorized keys, and port settings',
                    'icon' => 'fa fa-terminal',
                ],
                [
                    'label' => 'IP Blocker',
                    'description' => 'Allow or block visitors by IP address or CIDR range',
                    'icon' => 'fa fa-ban',
                ],
                [
                    'label' => '2FA Authentication',
                    'description' => 'Two-factor authentication for the panel and related logins',
                    'icon' => 'fa fa-shield',
                ],
                [
                    'label' => 'Hotlink Protection',
                    'description' => 'Prevent other sites from hotlinking your images and static files',
                    'icon' => 'fa fa-chain-broken',
                ],
            ],
        ],
        [
            'id' => 'database',
            'title' => 'Database',
            'items' => [
                ['label' => 'Manage DB', 'description' => 'Connections and users', 'icon' => 'fa fa-database', 'route' => 'hosts.db.manage'],
                ['label' => 'Adminer', 'description' => 'Web SQL client', 'icon' => 'fa fa-table'],
                ['label' => 'MySQL Manager', 'description' => 'MySQL tools', 'icon' => 'fa fa-server'],
                ['label' => 'PgSql', 'description' => 'PostgreSQL tools', 'icon' => 'fa fa-linux'],
            ],
        ],
    ],

];
