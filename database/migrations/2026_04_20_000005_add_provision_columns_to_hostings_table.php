<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hostings', function (Blueprint $table) {
            $table->string('provision_status')->default('pending')->after('status');
            $table->longText('provision_log')->nullable()->after('provision_status');
            $table->timestamp('provisioned_at')->nullable()->after('provision_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hostings', function (Blueprint $table) {
            $table->dropColumn(['provision_status', 'provision_log', 'provisioned_at']);
        });
    }
};
