<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Nwidart\Modules\Facades\Module;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);

        $manageSettingSeeder = 'Modules\\ManageSetting\\Database\\Seeders\\ManageSettingDatabaseSeeder';
        if (Module::isEnabled('ManageSetting') && class_exists($manageSettingSeeder)) {
            $this->call($manageSettingSeeder);
        }
    }
}
