<?php

namespace Modules\ManageDb\Repositories;

use App\Models\Hosting;
use Modules\ManageDb\Contracts\MysqlAdminPdoFactoryInterface;
use Modules\ManageDb\Contracts\MysqlPrivilegeCommandRepositoryInterface;
use PDOException;
use RuntimeException;

class PdoMysqlPrivilegeCommandRepository implements MysqlPrivilegeCommandRepositoryInterface
{
    public function __construct(
        private MysqlAdminPdoFactoryInterface $pdoFactory,
    ) {}

    public function revokeAllOnDatabaseForUser(Hosting $hosting, string $mysqlUser, string $databaseName): void
    {
        $pdo = $this->pdoFactory->connection();
        $qUser = $pdo->quote($mysqlUser);
        try {
            $pdo->exec("REVOKE ALL PRIVILEGES ON `{$databaseName}`.* FROM {$qUser}@'%'");
            $pdo->exec("REVOKE GRANT OPTION ON `{$databaseName}`.* FROM {$qUser}@'%'");
        } catch (PDOException $e) {
            $message = $e->getMessage();
            if (! str_contains($message, 'There is no such grant defined')) {
                throw new RuntimeException("Could not revoke access for {$mysqlUser} on {$databaseName}: {$message}");
            }
        }
    }

    public function grantAllOnDatabaseForUser(Hosting $hosting, string $mysqlUser, string $databaseName): void
    {
        $pdo = $this->pdoFactory->connection();
        $qUser = $pdo->quote($mysqlUser);
        try {
            $pdo->exec("GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO {$qUser}@'%'");
        } catch (PDOException $e) {
            throw new RuntimeException("Could not grant access for {$mysqlUser} on {$databaseName}: ".$e->getMessage());
        }
    }

    public function flushPrivileges(Hosting $hosting): void
    {
        $this->pdoFactory->connection()->exec('FLUSH PRIVILEGES');
    }
}
