<?php

namespace Modules\ManageDb\Services;

use App\Models\Hosting;
use Illuminate\Support\Facades\Cache;
use Modules\ManageDb\Models\HostingMysqlUserSecret;
use PDO;
use PDOException;
use RuntimeException;

class ManageDbService
{
    public function prefixForHosting(Hosting $hosting): string
    {
        $baseSource = (string) ($hosting->panel_username ?? '');
        if (trim($baseSource) === '') {
            $baseSource = (string) $hosting->domain;
        }

        $base = strtolower($baseSource);
        $base = preg_replace('/[^a-z0-9]+/', '_', $base) ?? '';
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'host';
        }

        return substr($base, 0, 24);
    }

    public function fullName(Hosting $hosting, string $raw): string
    {
        $raw = strtolower($raw);
        $raw = preg_replace('/[^a-z0-9_]/', '_', $raw) ?? '';
        $raw = trim($raw, '_');
        if ($raw === '') {
            throw new RuntimeException('Name is invalid after normalization.');
        }

        $prefix = $this->prefixForHosting($hosting);
        $name = $prefix.'_'.$raw;

        return substr($name, 0, 64);
    }

    /**
     * @return list<string>
     */
    public function listDatabases(Hosting $hosting): array
    {
        $pdo = $this->adminPdo();
        $prefix = $this->prefixForHosting($hosting).'\\_%';

        $stmt = $pdo->prepare('SHOW DATABASES LIKE :prefix');
        $stmt->execute(['prefix' => $prefix]);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);

        return array_values(array_map(fn (array $r): string => (string) $r[0], $rows));
    }

    /**
     * @return list<string>
     */
    public function listUsers(Hosting $hosting): array
    {
        $pdo = $this->adminPdo();
        $prefix = $this->prefixForHosting($hosting).'\\_%';

        $stmt = $pdo->prepare('SELECT User FROM mysql.user WHERE User LIKE :prefix ORDER BY User');
        $stmt->execute(['prefix' => $prefix]);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);

        return array_values(array_map(fn (array $r): string => (string) $r[0], $rows));
    }

    /**
     * @return array<int, array{user:string, database:string}>
     */
    public function listAccessGraphEdges(Hosting $hosting): array
    {
        $pdo = $this->adminPdo();
        $prefix = $this->prefixForHosting($hosting).'\\_%';
        $hostPrefix = $this->prefixForHosting($hosting).'_';

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

    public function createDatabase(Hosting $hosting, string $baseName): string
    {
        $name = $this->fullName($hosting, $baseName);
        $this->assertSafeIdentifier($name);

        $pdo = $this->adminPdo();
        try {
            $pdo->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (PDOException $e) {
            throw new RuntimeException('Could not create database: '.$e->getMessage());
        }

        return $name;
    }

    public function createUser(Hosting $hosting, string $baseName, string $password, ?string $grantDbName): string
    {
        $user = $this->fullName($hosting, $baseName);
        $this->assertSafeIdentifier($user);
        if (strlen($password) < 8) {
            throw new RuntimeException('Password must be at least 8 characters.');
        }

        $pdo = $this->adminPdo();
        $qUser = $pdo->quote($user);
        $qPass = $pdo->quote($password);
        try {
            $pdo->exec("CREATE USER {$qUser}@'%' IDENTIFIED BY {$qPass}");
            if ($grantDbName !== null && $grantDbName !== '') {
                $this->assertSafeIdentifier($grantDbName);
                if (! str_starts_with($grantDbName, $this->prefixForHosting($hosting).'_')) {
                    throw new RuntimeException('Grant database must use host prefix.');
                }
                $pdo->exec("GRANT ALL PRIVILEGES ON `{$grantDbName}`.* TO {$qUser}@'%'");
                $pdo->exec('FLUSH PRIVILEGES');
            }
        } catch (PDOException $e) {
            throw new RuntimeException('Could not create user: '.$e->getMessage());
        }

        $this->storeUserPassword($hosting, $user, $password);

        return $user;
    }

    /**
     * @param list<string> $users
     * @return array<string, string>
     */
    public function getStoredUserPasswords(Hosting $hosting, array $users): array
    {
        if ($users === []) {
            return [];
        }

        $rows = HostingMysqlUserSecret::query()
            ->where('hosting_id', $hosting->id)
            ->whereIn('mysql_username', $users)
            ->get(['mysql_username', 'password_encrypted']);

        $map = [];
        foreach ($rows as $row) {
            $username = strtolower((string) $row->mysql_username);
            $password = (string) $row->password_encrypted;
            if ($username !== '' && $password !== '') {
                $map[$username] = $password;
            }
        }

        return $map;
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
        $prefix = $this->prefixForHosting($hosting).'_';
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
            $this->assertSafeIdentifier($label);
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

        $existingUsers = array_flip($this->listUsers($hosting));
        $existingDatabases = array_flip($this->listDatabases($hosting));

        $pdo = $this->adminPdo();
        $missingUsers = array_values(array_filter($graphUsers, fn (string $u): bool => ! isset($existingUsers[$u])));
        $missingDatabases = array_values(array_filter($graphDatabases, fn (string $d): bool => ! isset($existingDatabases[$d])));
        $createdDatabases = 0;
        $createdUserCredentials = [];
        $createdDatabaseNames = [];

        foreach ($missingDatabases as $dbName) {
            try {
                $pdo->exec('CREATE DATABASE `'.$dbName.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $createdDatabases++;
                $createdDatabaseNames[] = $dbName;
            } catch (PDOException $e) {
                throw new RuntimeException("Could not auto-create database {$dbName}: ".$e->getMessage());
            }
        }

        foreach ($missingUsers as $user) {
            $providedPassword = (string) ($userPasswordByName[$user] ?? '');
            $generatedPassword = $providedPassword !== '' ? $providedPassword : (bin2hex(random_bytes(8)).'A1!');
            if (strlen($generatedPassword) < 8) {
                throw new RuntimeException("Password for {$user} must be at least 8 characters.");
            }
            $qUser = $pdo->quote($user);
            $qPass = $pdo->quote($generatedPassword);
            try {
                $pdo->exec("CREATE USER {$qUser}@'%' IDENTIFIED BY {$qPass}");
                $createdUserCredentials[] = $user.'='.$generatedPassword;
                $this->storeUserPassword($hosting, $user, $generatedPassword);
            } catch (PDOException $e) {
                throw new RuntimeException("Could not auto-create user {$user}: ".$e->getMessage());
            }
        }

        $graphUserSet = array_fill_keys(array_map('strtolower', $graphUsers), true);
        $droppedUserNames = [];
        try {
            $serverUsers = $this->listUsers($hosting);
        } catch (\Throwable) {
            $serverUsers = [];
        }
        foreach ($serverUsers as $serverUser) {
            if (isset($graphUserSet[strtolower($serverUser)])) {
                continue;
            }
            $qUser = $pdo->quote($serverUser);
            try {
                $pdo->exec("DROP USER IF EXISTS {$qUser}@'%'");
            } catch (PDOException $e) {
                try {
                    $pdo->exec("DROP USER {$qUser}@'%'");
                } catch (PDOException $e2) {
                    throw new RuntimeException("Could not remove MySQL user {$serverUser} (no longer in access graph): ".$e2->getMessage());
                }
            }
            $droppedUserNames[] = $serverUser;
            HostingMysqlUserSecret::query()
                ->where('hosting_id', $hosting->id)
                ->where('mysql_username', strtolower($serverUser))
                ->delete();
        }

        foreach ($graphUsers as $user) {
            $qUser = $pdo->quote($user);
            foreach ($graphDatabases as $dbName) {
                try {
                    $pdo->exec("REVOKE ALL PRIVILEGES ON `{$dbName}`.* FROM {$qUser}@'%'");
                    $pdo->exec("REVOKE GRANT OPTION ON `{$dbName}`.* FROM {$qUser}@'%'");
                } catch (PDOException $e) {
                    $message = $e->getMessage();
                    if (! str_contains($message, 'There is no such grant defined')) {
                        throw new RuntimeException("Could not revoke access for {$user} on {$dbName}: {$message}");
                    }
                }
            }
        }

        $grantCount = 0;
        $grantedPairs = [];
        foreach ($pairs as [$user, $dbName]) {
            $qUser = $pdo->quote($user);
            try {
                $pdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO {$qUser}@'%'");
                $grantCount++;
                $grantedPairs[] = $user.' -> '.$dbName;
            } catch (PDOException $e) {
                throw new RuntimeException("Could not grant access for {$user} on {$dbName}: ".$e->getMessage());
            }
        }

        $pdo->exec('FLUSH PRIVILEGES');

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

    private function storeUserPassword(Hosting $hosting, string $user, string $password): void
    {
        HostingMysqlUserSecret::query()->updateOrCreate(
            [
                'hosting_id' => $hosting->id,
                'mysql_username' => strtolower($user),
            ],
            [
                'password_encrypted' => $password,
            ]
        );
    }

    private function assertSafeIdentifier(string $name): void
    {
        if (! preg_match('/^[a-z0-9_]{1,64}$/', $name)) {
            throw new RuntimeException('Unsafe identifier.');
        }
    }

    /**
     * Host, port, and how app-side grants target MySQL (used for end-user DSNs).
     *
     * @return array{host: string, port: int, user_host: string}
     */
    public function mysqlConnectionDisplayInfo(): array
    {
        $params = $this->mysqlAdminConnectionParams();

        return [
            'host' => $params['host'],
            'port' => $params['port'],
            'user_host' => '%',
        ];
    }

    /**
     * @return array{host: string, port: int, user: string, pass: string}
     */
    private function mysqlAdminConnectionParams(): array
    {
        $settings = Cache::get('admin_settings.values', []);
        $mysqlEnabled = (bool) ($settings['mysql_enabled'] ?? false);

        if ($mysqlEnabled) {
            $host = (string) ($settings['mysql_host'] ?? '127.0.0.1');
            $port = (int) ($settings['mysql_port'] ?? 3306);
            $user = (string) ($settings['mysql_username'] ?? 'root');
            $pass = (string) ($settings['mysql_password'] ?? '');
        } else {
            $host = (string) config('manage_db.host', env('DB_HOST', '127.0.0.1'));
            $port = (int) config('manage_db.port', env('DB_PORT', 3306));
            $user = (string) config('manage_db.admin_user', env('DB_USERNAME', 'root'));
            $pass = (string) config('manage_db.admin_password', env('DB_PASSWORD', ''));
        }

        return [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'pass' => $pass,
        ];
    }

    private function adminPdo(): PDO
    {
        $params = $this->mysqlAdminConnectionParams();
        $host = $params['host'];
        $port = $params['port'];
        $user = $params['user'];
        $pass = $params['pass'];

        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Could not connect to MySQL admin: '.$e->getMessage());
        }
    }
}
