<?php

namespace Modules\Domains\Models;

use App\Models\Hosting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostDomainZoneRecord extends Model
{
    protected $table = 'host_domain_zone_records';

    protected $fillable = [
        'hosting_id',
        'zone_domain',
        'record_name',
        'record_type',
        'record_value',
        'mx_priority',
        'ttl',
    ];

    protected function casts(): array
    {
        return [
            'mx_priority' => 'integer',
            'ttl' => 'integer',
        ];
    }

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(Hosting::class);
    }
}
