<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('anthropic');
            $table->text('api_key');
            $table->string('model')->default('.claude-sonnet-4-6');
            $table->boolean('is_enabled')->default(true);
            $table->string('tone')->default('professional');
            $table->string('language')->default('es');
            $table->unsignedSmallInteger('max_tokens')->default(500);
            $table->text('custom_instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_ai_settings');
    }
};
