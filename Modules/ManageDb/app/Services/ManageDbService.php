<?php

namespace Modules\ManageDb\Services;

use App\Models\Hosting;
use Modules\ManageDb\Contracts\HostingMysqlUserSecretRepositoryInterface;
use Modules\ManageDb\Contracts\ManageDbNamingInterface;
use Modules\ManageDb\Contracts\MysqlAdminPdoFactoryInterface;
use Modules\ManageDb\Contracts\MysqlDatabaseRepositoryInterface;
use Modules\ManageDb\Contracts\MysqlPrivilegeCommandRepositoryInterface;
use Modules\ManageDb\Contracts\MysqlSchemaPrivilegesQueryRepositoryInterface;
use Modules\ManageDb\Contracts\MysqlUserRepositoryInterface;
use RuntimeException;

class ManageDbService
{
    public function __construct(
        private MysqlAdminPdoFactoryInterface $adminPdoFactory,
        private ManageDbNamingInterface $naming,
        private MysqlDatabaseRepositoryInterface $databaseRepository,
        private MysqlUserRepositoryInterface $userRepository,
        private MysqlSchemaPrivilegesQueryRepositoryInterface $schemaPrivilegesQueryRepository,
        private MysqlPrivilegeCommandRepositoryInterface $privilegeCommandRepository,
        private HostingMysqlUserSecretRepositoryInterface $userSecretRepository,
    ) {}

    public function prefixForHosting(Hosting $hosting): string
    {
        return $this->naming->prefixForHosting($hosting);
    }

    public function fullName(Hosting $hosting, string $raw): string
    {
        return $this->naming->fullName($hosting, $raw);
    }

    /**
     * @return list<string>
     */
    public function listDatabases(Hosting $hosting): array
    {
        return $this->databaseRepository->listByHostingPrefix($hosting);
    }

    /**
     * @return list<string>
     */
    public function listUsers(Hosting $hosting): array
    {
        return $this->userRepository->listByHostingPrefix($hosting);
    }

    /**
     * @return array<int, array{user:string, database:string}>
     */
    public function listAccessGraphEdges(Hosting $hosting): array
    {
        return $this->schemaPrivilegesQueryRepository->listAccessGraphEdges($hosting);
    }

    public function createDatabase(Hosting $hosting, string $baseName): string
    {
        $name = $this->naming->fullName($hosting, $baseName);
        $this->naming->assertSafeIdentifier($name);

        try {
            $this->databaseRepository->createByFullName($hosting, $name);
        } catch (RuntimeException $e) {
            throw new RuntimeException('Could not create database: '.$e->getMessage());
        }

        return $name;
    }

    public function createUser(Hosting $hosting, string $baseName, string $password, ?string $grantDbName): string
    {
        $user = $this->naming->fullName($hosting, $baseName);
        $this->naming->assertSafeIdentifier($user);

        $this->userRepository->createAtAnyHost($hosting, $user, $password, $grantDbName);
        $this->userSecretRepository->storePassword($hosting, $user, $password);

        return $user;
    }

    /**
     * @param list<string> $users
     * @return array<string, string>
     */
    public function getStoredUserPasswords(Hosting $hosting, array $users): array
    {
        return $this->userSecretRepository->getPasswordsForUsernames($hosting, $users);
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<int, array<string, mixed>> $edges
     * @return array{
     *   users:int,
     *   grants:int,
     *   created_users:int,
     *   created_databases:int,
     *   created_user_credentials: array<int, string>,
     *   created_database_names: array<int, string>,
     *   granted_pairs: array<int, string>,
     *   dropped_users: int,
     *   dropped_user_names: list<string>
     * }
     */
    public function applyAccessGraph(Hosting $hosting, array $nodes, array $edges): array
    {
        $prefix = $this->naming->prefixForHosting($hosting).'_';
        $userById = [];
        $userPasswordByName = [];
        $dbById = [];

        foreach ($nodes as $node) {
            $id = (string) ($node['id'] ?? '');
            $kind = (string) ($node['kind'] ?? '');
            $label = strtolower((string) ($node['label'] ?? ''));
            if ($id === '' || $label === '') {
                continue;
            }
            $this->naming->assertSafeIdentifier($label);
            if (! str_starts_with($label, $prefix)) {
                throw new RuntimeException("Node name '{$label}' must use host prefix '{$prefix}'.");
            }

            if ($kind === 'user') {
                $userById[$id] = $label;
                $password = (string) ($node['password'] ?? '');
                if ($password !== '') {
                    $userPasswordByName[$label] = $password;
                }
            } elseif ($kind === 'database') {
                $dbById[$id] = $label;
            }
        }

        if ($dbById === []) {
            throw new RuntimeException('Graph must include at least one database node.');
        }

        $pairs = [];
        foreach ($edges as $edge) {
            $fromId = (string) ($edge['fromId'] ?? '');
            $toId = (string) ($edge['toId'] ?? '');
            if (! isset($userById[$fromId], $dbById[$toId])) {
                continue;
            }
            $pairs[$fromId.'::'.$toId] = [$userById[$fromId], $dbById[$toId]];
        }

        $graphUsers = array_values(array_unique(array_values($userById)));
        $graphDatabases = array_values(array_unique(array_values($dbById)));

        $existingUsers = array_flip($this->userRepository->listByHostingPrefix($hosting));
        $existingDatabases = array_flip($this->databaseRepository->listByHostingPrefix($hosting));

        $missingUsers = array_values(array_filter($graphUsers, fn (string $u): bool => ! isset($existingUsers[$u])));
        $missingDatabases = array_values(array_filter($graphDatabases, fn (string $d): bool => ! isset($existingDatabases[$d])));
        $createdDatabases = 0;
        $createdUserCredentials = [];
        $createdDatabaseNames = [];

        foreach ($missingDatabases as $dbName) {
            try {
                $this->databaseRepository->createByFullName($hosting, $dbName);
                $createdDatabases++;
                $createdDatabaseNames[] = $dbName;
            } catch (RuntimeException $e) {
                throw new RuntimeException("Could not auto-create database {$dbName}: ".$e->getMessage());
            }
        }

        foreach ($missingUsers as $user) {
            $providedPassword = (string) ($userPasswordByName[$user] ?? '');
            $generatedPassword = $providedPassword !== '' ? $providedPassword : (bin2hex(random_bytes(8)).'A1!');
            if (strlen($generatedPassword) < 8) {
                throw new RuntimeException("Password for {$user} must be at least 8 characters.");
            }
            try {
                $this->userRepository->createAtAnyHost($hosting, $user, $generatedPassword, null);
            } catch (RuntimeException $e) {
                if (str_contains($e->getMessage(), 'Password must be at least')) {
                    throw $e;
                }
                throw new RuntimeException("Could not auto-create user {$user}: ".$e->getMessage());
            }
            $createdUserCredentials[] = $user.'='.$generatedPassword;
            $this->userSecretRepository->storePassword($hosting, $user, $generatedPassword);
        }

        $graphUserSet = array_fill_keys(array_map('strtolower', $graphUsers), true);
        $droppedUserNames = [];
        try {
            $serverUsers = $this->userRepository->listByHostingPrefix($hosting);
        } catch (\Throwable) {
            $serverUsers = [];
        }
        foreach ($serverUsers as $serverUser) {
            if (isset($graphUserSet[strtolower($serverUser)])) {
                continue;
            }
            $this->userRepository->dropAtAnyHost($hosting, $serverUser);
            $droppedUserNames[] = $serverUser;
            $this->userSecretRepository->deleteByHostingAndUsername($hosting, strtolower($serverUser));
        }

        foreach ($graphUsers as $user) {
            foreach ($graphDatabases as $dbName) {
                $this->privilegeCommandRepository->revokeAllOnDatabaseForUser($hosting, $user, $dbName);
            }
        }

        $grantCount = 0;
        $grantedPairs = [];
        foreach ($pairs as [$user, $dbName]) {
            $this->privilegeCommandRepository->grantAllOnDatabaseForUser($hosting, $user, $dbName);
            $grantCount++;
            $grantedPairs[] = $user.' -> '.$dbName;
        }

        $this->privilegeCommandRepository->flushPrivileges($hosting);

        return [
            'users' => count($graphUsers),
            'grants' => $grantCount,
            'created_users' => count($missingUsers),
            'created_databases' => $createdDatabases,
            'created_user_credentials' => $createdUserCredentials,
            'created_database_names' => $createdDatabaseNames,
            'granted_pairs' => $grantedPairs,
            'dropped_users' => count($droppedUserNames),
            'dropped_user_names' => $droppedUserNames,
        ];
    }

    /**
     * @return array{host: string, port: int, user_host: string}
     */
    public function mysqlConnectionDisplayInfo(): array
    {
        return $this->adminPdoFactory->displayConnectionInfo();
    }
}
