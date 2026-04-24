<?php

namespace Modules\ManageDb\Contracts;

use App\Models\Hosting;

interface MysqlUserRepositoryInterface
{
    /**
     * @return list<string>
     */
    public function listByHostingPrefix(Hosting $hosting): array;

    public function createAtAnyHost(Hosting $hosting, string $mysqlUser, string $password, ?string $grantDatabaseName): void;

    public function dropAtAnyHost(Hosting $hosting, string $mysqlUser): void;
}
