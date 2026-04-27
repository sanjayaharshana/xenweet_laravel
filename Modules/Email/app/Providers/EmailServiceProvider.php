<?php

namespace Modules\Email\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class EmailServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Email';

    protected string $nameLower = 'email';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
