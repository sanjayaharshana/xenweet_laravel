<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hosting extends Model
{
    protected $fillable = [
        'domain',
        'server_ip',
        'host_root_path',
        'web_root_path',
        'plan',
        'panel_username',
        'panel_password',
        'status',
        'provision_status',
        'provision_log',
        'provisioned_at',
        'php_version',
        'disk_usage_mb',
    ];

    protected function casts(): array
    {
        return [
            'panel_password' => 'encrypted',
            'provisioned_at' => 'datetime',
        ];
    }
}
