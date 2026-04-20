<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hostings', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('server_ip', 45);
            $table->string('plan')->default('Starter');
            $table->string('status')->default('Active');
            $table->string('php_version')->default('8.3');
            $table->unsignedInteger('disk_usage_mb')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hostings');
    }
};
