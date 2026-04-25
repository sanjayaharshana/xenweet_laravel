<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('host_domains', function (Blueprint $table) {
            if (! Schema::hasColumn('host_domains', 'document_root')) {
                $table->string('document_root', 2048)->nullable()->after('share_document_root');
            }
        });
    }

    public function down(): void
    {
        Schema::table('host_domains', function (Blueprint $table) {
            if (Schema::hasColumn('host_domains', 'document_root')) {
                $table->dropColumn('document_root');
            }
        });
    }
};
