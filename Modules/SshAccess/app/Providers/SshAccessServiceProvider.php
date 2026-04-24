<?php

namespace Modules\SshAccess\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class SshAccessServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'SshAccess';

    protected string $nameLower = 'sshaccess';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
