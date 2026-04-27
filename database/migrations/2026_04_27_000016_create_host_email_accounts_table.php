<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_email_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hosting_id')->constrained('hostings')->cascadeOnDelete();
            $table->string('local_part', 64);
            $table->string('domain', 255);
            $table->text('password');
            $table->unsignedInteger('quota_mb')->default(1024);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['hosting_id', 'local_part', 'domain'], 'host_email_accounts_unique_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_email_accounts');
    }
};
