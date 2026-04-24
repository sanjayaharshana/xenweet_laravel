<?php

namespace Modules\ManageDb\Contracts;

use App\Models\Hosting;

interface HostingMysqlUserSecretRepositoryInterface
{
    /**
     * @param list<string> $usernames
     * @return array<string, string>
     */
    public function getPasswordsForUsernames(Hosting $hosting, array $usernames): array;

    public function storePassword(Hosting $hosting, string $mysqlUser, string $password): void;

    public function deleteByHostingAndUsername(Hosting $hosting, string $mysqlUsernameLower): void;
}
