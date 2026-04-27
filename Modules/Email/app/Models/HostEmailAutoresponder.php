<?php

namespace Modules\Email\Models;

use Illuminate\Database\Eloquent\Model;

class HostEmailAutoresponder extends Model
{
    protected $fillable = [
        'hosting_id',
        'email',
        'subject',
        'body',
        'enabled',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
