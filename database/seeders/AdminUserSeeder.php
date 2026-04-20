<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed a default admin user for the hosting panel.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('PANEL_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('PANEL_ADMIN_NAME', 'Panel Admin'),
                'password' => env('PANEL_ADMIN_PASSWORD', 'password123'),
            ]
        );
    }
}
