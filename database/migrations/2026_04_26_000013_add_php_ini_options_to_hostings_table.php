<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostings', function (Blueprint $table): void {
            if (! Schema::hasColumn('hostings', 'php_ini_options')) {
                $table->json('php_ini_options')->nullable()->after('php_extensions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hostings', function (Blueprint $table): void {
            if (Schema::hasColumn('hostings', 'php_ini_options')) {
                $table->dropColumn('php_ini_options');
            }
        });
    }
};
