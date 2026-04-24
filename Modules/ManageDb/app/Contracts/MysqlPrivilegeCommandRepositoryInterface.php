<?php

namespace Modules\ManageDb\Contracts;

use App\Models\Hosting;

interface MysqlPrivilegeCommandRepositoryInterface
{
    public function revokeAllOnDatabaseForUser(Hosting $hosting, string $mysqlUser, string $databaseName): void;

    public function grantAllOnDatabaseForUser(Hosting $hosting, string $mysqlUser, string $databaseName): void;

    public function flushPrivileges(Hosting $hosting): void;
}
