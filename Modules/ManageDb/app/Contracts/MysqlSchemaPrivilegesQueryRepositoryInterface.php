<?php

namespace Modules\ManageDb\Contracts;

use App\Models\Hosting;

interface MysqlSchemaPrivilegesQueryRepositoryInterface
{
    /**
     * @return array<int, array{user: string, database: string}>
     */
    public function listAccessGraphEdges(Hosting $hosting): array;
}
