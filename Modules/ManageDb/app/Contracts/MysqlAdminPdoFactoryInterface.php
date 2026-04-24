<?php

namespace Modules\ManageDb\Contracts;

use PDO;

interface MysqlAdminPdoFactoryInterface
{
    public function connection(): PDO;

    /**
     * @return array{host: string, port: int, user_host: string}
     */
    public function displayConnectionInfo(): array;
}
