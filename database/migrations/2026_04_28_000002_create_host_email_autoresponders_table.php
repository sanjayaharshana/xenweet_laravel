<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_email_autoresponders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hosting_id')->constrained('hostings')->cascadeOnDelete();
            $table->string('email', 255);
            $table->string('subject', 255);
            $table->text('body');
            $table->boolean('enabled')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['hosting_id', 'email'], 'host_email_autoresponders_unique_mailbox');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_email_autoresponders');
    }
};
