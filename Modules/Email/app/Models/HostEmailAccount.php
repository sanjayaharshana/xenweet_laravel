<?php

namespace Modules\Email\Models;

use Illuminate\Database\Eloquent\Model;

class HostEmailAccount extends Model
{
    protected $fillable = [
        'hosting_id',
        'local_part',
        'domain',
        'password',
        'quota_mb',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'quota_mb' => 'integer',
        ];
    }
}
