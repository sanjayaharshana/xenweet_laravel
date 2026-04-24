<?php

namespace Modules\SslTls\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class SslTlsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'SslTls';

    protected string $nameLower = 'ssltls';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
