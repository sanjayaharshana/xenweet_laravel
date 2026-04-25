<?php

namespace Modules\Domains\Models;

use App\Models\Hosting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostDomain extends Model
{
    protected $fillable = [
        'hosting_id',
        'type',
        'domain',
        'share_document_root',
    ];

    protected function casts(): array
    {
        return [
            'share_document_root' => 'boolean',
        ];
    }

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(Hosting::class);
    }
}
