<?php

namespace Modules\ManageDb\Repositories;

use App\Models\Hosting;
use Modules\ManageDb\Contracts\ManageDbNamingInterface;
use Modules\ManageDb\Contracts\MysqlAdminPdoFactoryInterface;
use Modules\ManageDb\Contracts\MysqlUserRepositoryInterface;
use PDO;
use PDOException;
use RuntimeException;

class PdoMysqlUserRepository implements MysqlUserRepositoryInterface
{
    public function __construct(
        private MysqlAdminPdoFactoryInterface $pdoFactory,
        private ManageDbNamingInterface $naming,
    ) {}

    public function listByHostingPrefix(Hosting $hosting): array
    {
        $pdo = $this->pdoFactory->connection();
        $prefix = $this->naming->prefixForHosting($hosting).'\\_%';

        $stmt = $pdo->prepare('SELECT User FROM mysql.user WHERE User LIKE :prefix ORDER BY User');
        $stmt->execute(['prefix' => $prefix]);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);

        return array_values(array_map(fn (array $r): string => (string) $r[0], $rows));
    }

    public function createAtAnyHost(Hosting $hosting, string $mysqlUser, string $password, ?string $grantDatabaseName): void
    {
        $this->naming->assertSafeIdentifier($mysqlUser);
        if (strlen($password) < 8) {
            throw new RuntimeException('Password must be at least 8 characters.');
        }

        $pdo = $this->pdoFactory->connection();
        $qUser = $pdo->quote($mysqlUser);
        $qPass = $pdo->quote($password);
        try {
            $pdo->exec("CREATE USER {$qUser}@'%' IDENTIFIED BY {$qPass}");
            if ($grantDatabaseName !== null && $grantDatabaseName !== '') {
                $this->naming->assertSafeIdentifier($grantDatabaseName);
                if (! str_starts_with($grantDatabaseName, $this->naming->prefixForHosting($hosting).'_')) {
                    throw new RuntimeException('Grant database must use host prefix.');
                }
                $pdo->exec("GRANT ALL PRIVILEGES ON `{$grantDatabaseName}`.* TO {$qUser}@'%'");
                $pdo->exec('FLUSH PRIVILEGES');
            }
        } catch (PDOException $e) {
            throw new RuntimeException('Could not create user: '.$e->getMessage());
        }
    }

    public function dropAtAnyHost(Hosting $hosting, string $mysqlUser): void
    {
        $pdo = $this->pdoFactory->connection();
        $qUser = $pdo->quote($mysqlUser);
        try {
            $pdo->exec("DROP USER IF EXISTS {$qUser}@'%'");
        } catch (PDOException $e) {
            try {
                $pdo->exec("DROP USER {$qUser}@'%'");
            } catch (PDOException $e2) {
                throw new RuntimeException("Could not remove MySQL user {$mysqlUser} (no longer in access graph): ".$e2->getMessage());
            }
        }
    }
}
