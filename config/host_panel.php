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
                    'route' => 'hosts.ssh-access',
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
        [
            'id' => 'domains',
            'title' => 'Domains',
            'items' => [
                ['label' => 'Domain', 'description' => 'Primary domain and domain management', 'icon' => 'fa fa-globe', 'route' => 'hosts.domains.index'],
                ['label' => 'Redirects', 'description' => 'URL and domain redirects', 'icon' => 'fa fa-random', 'route' => 'hosts.domains.index', 'route_parameters' => ['tab' => 'redirects']],
                ['label' => 'Zone Editor', 'description' => 'DNS zone records and live public DNS', 'icon' => 'fa fa-sitemap', 'route' => 'hosts.domains.index', 'route_parameters' => ['tab' => 'zone']],
                ['label' => 'Dynamic DNS', 'description' => 'Manage dynamic DNS host updates', 'icon' => 'fa fa-refresh'],
            ],
        ],
        [
            'id' => 'software',
            'title' => 'Software',
            'items' => [
                [
                    'label' => 'PHP Version',
                    'description' => 'Select or switch the PHP version for this host',
                    'icon' => 'fa fa-code',
                    'route' => 'hosts.php-version',
                ],
                [
                    'label' => 'Redis',
                    'description' => 'In-memory cache and data store; enable and configure Redis',
                    'icon' => 'fa fa-bolt',
                ],
                [
                    'label' => 'App Manager',
                    'description' => 'One-click apps and runtimes (WordPress, frameworks, and more)',
                    'icon' => 'fa fa-th',
                ],
                [
                    'label' => 'PHP PEAR',
                    'description' => 'PEAR packages and the PEAR installer for this account',
                    'icon' => 'fa fa-cube',
                ],
                [
                    'label' => 'Web Optimizer',
                    'description' => 'Caching, compression, and static asset performance',
                    'icon' => 'fa fa-rocket',
                ],
            ],
        ],
    ],

];
