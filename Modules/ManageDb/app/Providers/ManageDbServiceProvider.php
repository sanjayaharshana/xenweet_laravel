<?php

namespace Modules\ManageDb\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ManageDbServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ManageDb';

    protected string $nameLower = 'managedb';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
