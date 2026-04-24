<?php

namespace Modules\ManageDb\Models;

use Illuminate\Database\Eloquent\Model;

class HostingMysqlUserSecret extends Model
{
    protected $table = 'hosting_mysql_user_secrets';

    protected $fillable = [
        'hosting_id',
        'mysql_username',
        'password_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'password_encrypted' => 'encrypted',
        ];
    }
}

