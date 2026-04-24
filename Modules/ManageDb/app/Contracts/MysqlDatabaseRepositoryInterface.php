<?php

namespace Modules\ManageDb\Contracts;

use App\Models\Hosting;

interface MysqlDatabaseRepositoryInterface
{
    /**
     * @return list<string>
     */
    public function listByHostingPrefix(Hosting $hosting): array;

    public function createByFullName(Hosting $hosting, string $fullName): void;
}
