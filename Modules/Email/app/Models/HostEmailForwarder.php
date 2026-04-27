<?php

namespace Modules\Email\Models;

use Illuminate\Database\Eloquent\Model;

class HostEmailForwarder extends Model
{
    protected $fillable = [
        'hosting_id',
        'source_email',
        'destination_email',
        'status',
    ];
}
