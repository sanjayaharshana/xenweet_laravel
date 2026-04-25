<?php

namespace Modules\Domains\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class DomainsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Domains';

    protected string $nameLower = 'domains';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
