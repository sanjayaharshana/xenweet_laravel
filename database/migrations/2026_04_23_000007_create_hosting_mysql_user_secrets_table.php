<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_mysql_user_secrets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hosting_id')->constrained()->cascadeOnDelete();
            $table->string('mysql_username', 64);
            $table->text('password_encrypted');
            $table->timestamps();

            $table->unique(['hosting_id', 'mysql_username'], 'hosting_mysql_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_mysql_user_secrets');
    }
};

