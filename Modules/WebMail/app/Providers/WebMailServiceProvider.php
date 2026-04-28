<?php

namespace Modules\WebMail\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class WebMailServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'WebMail';

    protected string $nameLower = 'webmail';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
