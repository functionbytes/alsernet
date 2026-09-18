<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('seo_keywords')->comment('Translated content by locale');
            $table->string('content_hash', 64)->nullable()->after('translations')->comment('MD5 of long_description for duplicate detection');
        });

        Schema::create('supplier_prompt_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prompt_id')->constrained('supplier_prompts')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'prompt_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_prompt_favorites');

        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            $table->dropColumn(['content_hash', 'translations']);
        });
    }
};
