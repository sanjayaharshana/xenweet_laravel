<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_ssl_stores', function (Blueprint $table) {
            $table->timestamp('letsencrypt_issued_at')->nullable()->after('san_hostnames');
            $table->boolean('letsencrypt_staging')->default(false)->after('letsencrypt_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_ssl_stores', function (Blueprint $table) {
            $table->dropColumn(['letsencrypt_issued_at', 'letsencrypt_staging']);
        });
    }
};
