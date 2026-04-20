<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hosting extends Model
{
    protected $fillable = [
        'domain',
        'server_ip',
        'plan',
        'panel_username',
        'panel_password',
        'status',
        'php_version',
        'disk_usage_mb',
    ];

    protected function casts(): array
    {
        return [
            'panel_password' => 'encrypted',
        ];
    }
}
