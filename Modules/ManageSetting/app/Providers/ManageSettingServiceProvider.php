<?php

namespace Modules\ManageSetting\Providers;

use Modules\ManageSetting\Services\ManageSettingService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ManageSettingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ManageSetting';

    protected string $nameLower = 'managesetting';

    protected array $providers = [];

    public function register(): void
    {
        $this->app->singleton(ManageSettingService::class);
        parent::register();
    }
}
