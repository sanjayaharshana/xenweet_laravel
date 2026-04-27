<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_email_forwarders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hosting_id')->constrained('hostings')->cascadeOnDelete();
            $table->string('source_email', 255);
            $table->string('destination_email', 255);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['hosting_id', 'source_email', 'destination_email'], 'host_email_forwarders_unique_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_email_forwarders');
    }
};
