<?php

namespace Modules\Plan\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'monthly_price',
        'disk_limit_mb',
        'bandwidth_gb',
        'max_domains',
        'status',
        'description',
    ];
}
