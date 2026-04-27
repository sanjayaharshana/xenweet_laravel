<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostings', function (Blueprint $table): void {
            $table->boolean('hotlink_protection_enabled')->default(false)->after('panel_2fa_recovery_codes');
            $table->boolean('hotlink_allow_direct_requests')->default(true)->after('hotlink_protection_enabled');
            $table->json('hotlink_allowed_domains')->nullable()->after('hotlink_allow_direct_requests');
            $table->json('hotlink_blocked_extensions')->nullable()->after('hotlink_allowed_domains');
        });
    }

    public function down(): void
    {
        Schema::table('hostings', function (Blueprint $table): void {
            $table->dropColumn([
                'hotlink_protection_enabled',
                'hotlink_allow_direct_requests',
                'hotlink_allowed_domains',
                'hotlink_blocked_extensions',
            ]);
        });
    }
};
