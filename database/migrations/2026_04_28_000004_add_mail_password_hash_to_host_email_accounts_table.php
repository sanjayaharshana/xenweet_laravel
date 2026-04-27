<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('host_email_accounts', function (Blueprint $table): void {
            $table->text('mail_password_hash')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('host_email_accounts', function (Blueprint $table): void {
            $table->dropColumn('mail_password_hash');
        });
    }
};
