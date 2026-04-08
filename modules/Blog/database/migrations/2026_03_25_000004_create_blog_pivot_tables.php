<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('category_id');

            $table->foreign('post_id')
                ->references('id')
                ->on('blog_posts')
                ->cascadeOnDelete();

            $table->foreign('category_id')
                ->references('id')
                ->on('blog_categories')
                ->cascadeOnDelete();

            $table->index('post_id');
            $table->index('category_id');
        });

        Schema::create('blog_post_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('tag_id');

            $table->foreign('post_id')
                ->references('id')
                ->on('blog_posts')
                ->cascadeOnDelete();

            $table->foreign('tag_id')
                ->references('id')
                ->on('blog_tags')
                ->cascadeOnDelete();

            $table->index('post_id');
            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_tags');
        Schema::dropIfExists('blog_post_categories');
    }
};
