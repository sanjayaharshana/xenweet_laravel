<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_id')->constrained('hostings')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('domain', 255);
            $table->boolean('share_document_root')->default(false);
            $table->timestamps();

            $table->index('hosting_id');
            $table->unique('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_domains');
    }
};
