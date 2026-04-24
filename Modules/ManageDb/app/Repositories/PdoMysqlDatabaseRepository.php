<?php

namespace Modules\ManageDb\Repositories;

use App\Models\Hosting;
use Modules\ManageDb\Contracts\ManageDbNamingInterface;
use Modules\ManageDb\Contracts\MysqlAdminPdoFactoryInterface;
use Modules\ManageDb\Contracts\MysqlDatabaseRepositoryInterface;
use PDO;
use PDOException;
use RuntimeException;

class PdoMysqlDatabaseRepository implements MysqlDatabaseRepositoryInterface
{
    public function __construct(
        private MysqlAdminPdoFactoryInterface $pdoFactory,
        private ManageDbNamingInterface $naming,
    ) {}

    public function listByHostingPrefix(Hosting $hosting): array
    {
        $pdo = $this->pdoFactory->connection();
        $prefix = $this->naming->prefixForHosting($hosting).'\\_%';

        $stmt = $pdo->prepare('SHOW DATABASES LIKE :prefix');
        $stmt->execute(['prefix' => $prefix]);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);

        return array_values(array_map(fn (array $r): string => (string) $r[0], $rows));
    }

    public function createByFullName(Hosting $hosting, string $fullName): void
    {
        $this->naming->assertSafeIdentifier($fullName);
        $pdo = $this->pdoFactory->connection();
        try {
            $pdo->exec('CREATE DATABASE `'.$fullName.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (PDOException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
}
