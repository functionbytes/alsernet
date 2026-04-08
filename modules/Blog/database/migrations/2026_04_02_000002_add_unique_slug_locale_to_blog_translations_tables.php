<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // blog_post_translations: drop plain index, add unique constraint
        Schema::table('blog_post_translations', function (Blueprint $table) {
            $table->dropIndex(['locale', 'slug']);
            $table->unique(['locale', 'slug'], 'blog_post_translations_locale_slug_unique');
        });

        // blog_category_translations: drop plain index, add unique constraint
        Schema::table('blog_category_translations', function (Blueprint $table) {
            $table->dropIndex(['locale', 'slug']);
            $table->unique(['locale', 'slug'], 'blog_category_translations_locale_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('blog_post_translations', function (Blueprint $table) {
            $table->dropUnique('blog_post_translations_locale_slug_unique');
            $table->index(['locale', 'slug']);
        });

        Schema::table('blog_category_translations', function (Blueprint $table) {
            $table->dropUnique('blog_category_translations_locale_slug_unique');
            $table->index(['locale', 'slug']);
        });
    }
};
