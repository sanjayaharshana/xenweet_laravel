<?php

namespace Modules\Email\Models;

use Illuminate\Database\Eloquent\Model;

class HostEmailFilter extends Model
{
    protected $fillable = [
        'hosting_id',
        'scope',
        'email',
        'rule_name',
        'condition_type',
        'condition_value',
        'action_type',
        'action_value',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }
}
