<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->longText('content')->nullable();
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('seo_image_url', 500)->nullable();
            $table->boolean('seo_noindex')->default(false);
            $table->enum('status', ['draft', 'published', 'pending'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'locale']);
            $table->index(['locale', 'slug']);
            $table->index(['locale', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_translations');
    }
};
