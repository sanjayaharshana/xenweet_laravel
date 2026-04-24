<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostingSslStore extends Model
{
    protected $fillable = [
        'hosting_id',
        'key_type',
        'private_key_pem',
        'csr_pem',
        'certificate_pem',
        'certificate_chain_pem',
        'san_hostnames',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'private_key_pem' => 'encrypted',
            'san_hostnames' => 'array',
        ];
    }

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(Hosting::class);
    }
}
