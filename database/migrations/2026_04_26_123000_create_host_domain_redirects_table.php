<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_domain_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_id')->constrained('hostings')->cascadeOnDelete();
            $table->string('source_domain', 255);
            $table->string('redirect_type', 16);
            $table->string('redirect_url', 2048);
            $table->timestamps();

            $table->unique(['hosting_id', 'source_domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_domain_redirects');
    }
};
