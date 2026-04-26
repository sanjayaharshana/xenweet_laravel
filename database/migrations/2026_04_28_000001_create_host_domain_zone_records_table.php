<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_domain_zone_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_id')->constrained('hostings')->cascadeOnDelete();
            $table->string('zone_domain', 255);
            $table->string('record_name', 255);
            $table->string('record_type', 8);
            $table->text('record_value');
            $table->unsignedSmallInteger('mx_priority')->nullable();
            $table->unsignedInteger('ttl')->default(3600);
            $table->timestamps();

            $table->index(['hosting_id', 'zone_domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_domain_zone_records');
    }
};
