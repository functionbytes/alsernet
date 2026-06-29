<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title', 255)->nullable();
            $table->string('slug', 255)->nullable();
            $table->string('description', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->timestamps();

            $table->unique(['blog_post_id', 'locale']);
            $table->index(['locale', 'slug']);
        });

        Schema::create('blog_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_category_id')->constrained('blog_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 120);
            $table->string('slug', 255)->nullable();
            $table->string('description', 400)->nullable();
            $table->timestamps();

            $table->unique(['blog_category_id', 'locale']);
            $table->index(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_category_translations');
        Schema::dropIfExists('blog_post_translations');
    }
};
