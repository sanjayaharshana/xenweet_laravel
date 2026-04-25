<?php

namespace Modules\ManageDb\Repositories;

use Modules\ManageDb\Contracts\MysqlAdminPdoFactoryInterface;
use Modules\ManageSetting\Support\Settings;
use PDO;
use PDOException;
use RuntimeException;

class MysqlAdminPdoFactory implements MysqlAdminPdoFactoryInterface
{
    /**
     * @return array{host: string, port: int, user: string, pass: string}
     */
    private function adminParams(): array
    {
        $mysqlEnabled = Settings::bool('mysql_enabled', false);

        if ($mysqlEnabled) {
            $host = (string) Settings::get('mysql_host', '127.0.0.1');
            $port = (int) Settings::get('mysql_port', 3306);
            $user = (string) Settings::get('mysql_username', 'root');
            $pass = (string) Settings::get('mysql_password', '');
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

    public function connection(): PDO
    {
        $p = $this->adminParams();
        $dsn = "mysql:host={$p['host']};port={$p['port']};charset=utf8mb4";
        try {
            return new PDO($dsn, $p['user'], $p['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Could not connect to MySQL admin: '.$e->getMessage());
        }
    }

    public function displayConnectionInfo(): array
    {
        $p = $this->adminParams();

        return [
            'host' => $p['host'],
            'port' => $p['port'],
            'user_host' => '%',
        ];
    }
}
