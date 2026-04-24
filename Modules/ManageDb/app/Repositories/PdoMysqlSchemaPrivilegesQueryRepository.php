<?php

namespace Modules\ManageDb\Repositories;

use App\Models\Hosting;
use Modules\ManageDb\Contracts\ManageDbNamingInterface;
use Modules\ManageDb\Contracts\MysqlAdminPdoFactoryInterface;
use Modules\ManageDb\Contracts\MysqlSchemaPrivilegesQueryRepositoryInterface;
use PDO;

class PdoMysqlSchemaPrivilegesQueryRepository implements MysqlSchemaPrivilegesQueryRepositoryInterface
{
    public function __construct(
        private MysqlAdminPdoFactoryInterface $pdoFactory,
        private ManageDbNamingInterface $naming,
    ) {}

    public function listAccessGraphEdges(Hosting $hosting): array
    {
        $pdo = $this->pdoFactory->connection();
        $prefix = $this->naming->prefixForHosting($hosting).'\\_%';
        $hostPrefix = $this->naming->prefixForHosting($hosting).'_';

        $stmt = $pdo->prepare(
            'SELECT DISTINCT GRANTEE, TABLE_SCHEMA
             FROM information_schema.SCHEMA_PRIVILEGES
             WHERE TABLE_SCHEMA LIKE :prefix'
        );
        $stmt->execute(['prefix' => $prefix]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $edges = [];
        foreach ($rows as $row) {
            $grantee = (string) ($row['GRANTEE'] ?? '');
            $database = strtolower((string) ($row['TABLE_SCHEMA'] ?? ''));
            if (! preg_match("/^'([^']+)'@'.*'$/", $grantee, $matches)) {
                continue;
            }
            $user = strtolower((string) ($matches[1] ?? ''));
            if (
                $user === '' || $database === '' ||
                ! str_starts_with($user, $hostPrefix) ||
                ! str_starts_with($database, $hostPrefix)
            ) {
                continue;
            }
            $edges[$user.'::'.$database] = [
                'user' => $user,
                'database' => $database,
            ];
        }

        return array_values($edges);
    }
}
