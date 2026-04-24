<?php

namespace Modules\PhpVersion\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class PhpVersionServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'PhpVersion';

    protected string $nameLower = 'phpversion';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
