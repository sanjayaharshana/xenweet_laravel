<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_email_filters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hosting_id')->constrained('hostings')->cascadeOnDelete();
            $table->string('scope', 20)->default('global');
            $table->string('email', 255)->nullable();
            $table->string('rule_name', 120);
            $table->string('condition_type', 30);
            $table->string('condition_value', 255);
            $table->string('action_type', 40);
            $table->string('action_value', 255)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['hosting_id', 'rule_name'], 'host_email_filters_unique_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_email_filters');
    }
};
