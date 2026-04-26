<?php

namespace Modules\Domains\Models;

use App\Models\Hosting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostDomainRedirect extends Model
{
    protected $fillable = [
        'hosting_id',
        'source_domain',
        'redirect_type',
        'redirect_url',
    ];

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(Hosting::class);
    }
}
