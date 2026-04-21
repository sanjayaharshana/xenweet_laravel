<?php

namespace Modules\ManageDb\Services;

use App\Models\Hosting;
use PDO;
use PDOException;
use RuntimeException;

class ManageDbService
{
    public function prefixForHosting(Hosting $hosting): string
    {
        $base = strtolower((string) $hosting->domain);
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

        return $user;
    }

    private function assertSafeIdentifier(string $name): void
    {
        if (! preg_match('/^[a-z0-9_]{1,64}$/', $name)) {
            throw new RuntimeException('Unsafe identifier.');
        }
    }

    private function adminPdo(): PDO
    {
        $host = (string) config('manage_db.host', env('DB_HOST', '127.0.0.1'));
        $port = (int) config('manage_db.port', env('DB_PORT', 3306));
        $user = (string) config('manage_db.admin_user', env('DB_USERNAME', 'root'));
        $pass = (string) config('manage_db.admin_password', env('DB_PASSWORD', ''));

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
