<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostings', function (Blueprint $table): void {
            if (! Schema::hasColumn('hostings', 'php_extensions')) {
                $table->json('php_extensions')->nullable()->after('php_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hostings', function (Blueprint $table): void {
            if (Schema::hasColumn('hostings', 'php_extensions')) {
                $table->dropColumn('php_extensions');
            }
        });
    }
};
