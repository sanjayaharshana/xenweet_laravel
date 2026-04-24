<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostings', function (Blueprint $table) {
            $table->json('ssl_san_hostnames')->nullable()->after('domain');
        });
    }

    public function down(): void
    {
        Schema::table('hostings', function (Blueprint $table) {
            $table->dropColumn('ssl_san_hostnames');
        });
    }
};
