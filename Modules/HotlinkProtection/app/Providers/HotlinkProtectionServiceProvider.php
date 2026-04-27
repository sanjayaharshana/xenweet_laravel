<?php

namespace Modules\HotlinkProtection\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class HotlinkProtectionServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'HotlinkProtection';

    protected string $nameLower = 'hotlinkprotection';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
