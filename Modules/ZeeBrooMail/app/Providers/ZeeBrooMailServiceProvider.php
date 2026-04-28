<?php

namespace Modules\ZeeBrooMail\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ZeeBrooMailServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ZeeBrooMail';

    protected string $nameLower = 'zeebroomail';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
