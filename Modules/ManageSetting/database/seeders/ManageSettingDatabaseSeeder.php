<?php

namespace Modules\ManageSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ManageSetting\Services\ManageSettingService;

class ManageSettingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tabs = (array) config('admin_settings.tabs', []);
        $defaults = [];

        foreach ($tabs as $tab) {
            foreach ((array) ($tab['fields'] ?? []) as $field) {
                $key = (string) ($field['key'] ?? '');
                if ($key === '') {
                    continue;
                }

                $defaults[$key] = $field['default'] ?? null;
            }
        }

        app(ManageSettingService::class)->seedDefaults($defaults);
    }
}
