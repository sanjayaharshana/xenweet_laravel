<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostings', function (Blueprint $table): void {
            $table->boolean('panel_2fa_enabled')->default(false)->after('panel_password');
            $table->text('panel_2fa_secret')->nullable()->after('panel_2fa_enabled');
            $table->json('panel_2fa_recovery_codes')->nullable()->after('panel_2fa_secret');
        });
    }

    public function down(): void
    {
        Schema::table('hostings', function (Blueprint $table): void {
            $table->dropColumn([
                'panel_2fa_enabled',
                'panel_2fa_secret',
                'panel_2fa_recovery_codes',
            ]);
        });
    }
};
