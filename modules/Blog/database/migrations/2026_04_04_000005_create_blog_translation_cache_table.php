<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_translation_cache', function (Blueprint $table) {
            $table->id();
            $table->string('source_hash', 64);
            $table->string('source_locale', 5);
            $table->string('target_locale', 5);
            $table->string('field_name', 30);
            $table->longText('translated_text');
            $table->string('provider', 30)->default('deepl');
            $table->timestamps();

            $table->unique(['source_hash', 'source_locale', 'target_locale', 'field_name'], 'blog_translation_cache_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_translation_cache');
    }
};
