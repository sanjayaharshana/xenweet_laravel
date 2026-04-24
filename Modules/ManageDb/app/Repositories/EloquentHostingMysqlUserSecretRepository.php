<?php

namespace Modules\ManageDb\Repositories;

use App\Models\Hosting;
use Modules\ManageDb\Contracts\HostingMysqlUserSecretRepositoryInterface;
use Modules\ManageDb\Models\HostingMysqlUserSecret;

class EloquentHostingMysqlUserSecretRepository implements HostingMysqlUserSecretRepositoryInterface
{
    public function getPasswordsForUsernames(Hosting $hosting, array $usernames): array
    {
        if ($usernames === []) {
            return [];
        }

        $rows = HostingMysqlUserSecret::query()
            ->where('hosting_id', $hosting->id)
            ->whereIn('mysql_username', $usernames)
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

    public function storePassword(Hosting $hosting, string $mysqlUser, string $password): void
    {
        HostingMysqlUserSecret::query()->updateOrCreate(
            [
                'hosting_id' => $hosting->id,
                'mysql_username' => strtolower($mysqlUser),
            ],
            [
                'password_encrypted' => $password,
            ]
        );
    }

    public function deleteByHostingAndUsername(Hosting $hosting, string $mysqlUsernameLower): void
    {
        HostingMysqlUserSecret::query()
            ->where('hosting_id', $hosting->id)
            ->where('mysql_username', strtolower($mysqlUsernameLower))
            ->delete();
    }
}
