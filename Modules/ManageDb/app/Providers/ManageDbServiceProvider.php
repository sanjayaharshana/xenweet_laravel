<?php

namespace Modules\ManageDb\Providers;

use Modules\ManageDb\Contracts\HostingMysqlUserSecretRepositoryInterface;
use Modules\ManageDb\Contracts\ManageDbNamingInterface;
use Modules\ManageDb\Contracts\MysqlAdminPdoFactoryInterface;
use Modules\ManageDb\Contracts\MysqlDatabaseRepositoryInterface;
use Modules\ManageDb\Contracts\MysqlPrivilegeCommandRepositoryInterface;
use Modules\ManageDb\Contracts\MysqlSchemaPrivilegesQueryRepositoryInterface;
use Modules\ManageDb\Contracts\MysqlUserRepositoryInterface;
use Modules\ManageDb\Repositories\EloquentHostingMysqlUserSecretRepository;
use Modules\ManageDb\Repositories\ManageDbNaming;
use Modules\ManageDb\Repositories\MysqlAdminPdoFactory;
use Modules\ManageDb\Repositories\PdoMysqlDatabaseRepository;
use Modules\ManageDb\Repositories\PdoMysqlPrivilegeCommandRepository;
use Modules\ManageDb\Repositories\PdoMysqlSchemaPrivilegesQueryRepository;
use Modules\ManageDb\Repositories\PdoMysqlUserRepository;
use Modules\ManageDb\Services\ManageDbService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ManageDbServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ManageDb';

    protected string $nameLower = 'managedb';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        $this->registerRepositoryBindings();
        $this->app->singleton(ManageDbService::class);
        parent::register();
    }

    protected function registerRepositoryBindings(): void
    {
        $this->app->singleton(MysqlAdminPdoFactoryInterface::class, MysqlAdminPdoFactory::class);
        $this->app->singleton(ManageDbNamingInterface::class, ManageDbNaming::class);
        $this->app->singleton(MysqlDatabaseRepositoryInterface::class, PdoMysqlDatabaseRepository::class);
        $this->app->singleton(MysqlUserRepositoryInterface::class, PdoMysqlUserRepository::class);
        $this->app->singleton(MysqlSchemaPrivilegesQueryRepositoryInterface::class, PdoMysqlSchemaPrivilegesQueryRepository::class);
        $this->app->singleton(MysqlPrivilegeCommandRepositoryInterface::class, PdoMysqlPrivilegeCommandRepository::class);
        $this->app->singleton(HostingMysqlUserSecretRepositoryInterface::class, EloquentHostingMysqlUserSecretRepository::class);
    }
}
