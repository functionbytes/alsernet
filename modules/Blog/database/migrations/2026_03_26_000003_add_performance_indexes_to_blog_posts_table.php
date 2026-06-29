<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // Composite index for featured posts queries (status + is_featured + published_at)
            $table->index(
                ['status', 'is_featured', 'published_at'],
                'blog_posts_status_featured_published_index'
            );

            // Composite index for author's posts listing (replaces individual user_id index)
            $table->index(
                ['user_id', 'status'],
                'blog_posts_user_id_status_index'
            );

            // Index on views for most-viewed scope ordering
            $table->index('views', 'blog_posts_views_index');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex('blog_posts_status_featured_published_index');
            $table->dropIndex('blog_posts_user_id_status_index');
            $table->dropIndex('blog_posts_views_index');
        });
    }
};
