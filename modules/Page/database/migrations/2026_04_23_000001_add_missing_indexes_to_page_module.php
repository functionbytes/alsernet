<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            if (! $this->indexExists('pages', 'pages_pending_approval_index')) {
                $table->index('pending_approval', 'pages_pending_approval_index');
            }
            if (! $this->indexExists('pages', 'pages_seo_noindex_index')) {
                $table->index('seo_noindex', 'pages_seo_noindex_index');
            }
        });

        Schema::table('page_translations', function (Blueprint $table) {
            if (! $this->indexExists('page_translations', 'page_translations_slug_index')) {
                $table->index('slug', 'page_translations_slug_index');
            }
            if (! $this->indexExists('page_translations', 'page_translations_status_index')) {
                $table->index('status', 'page_translations_status_index');
            }
            if (! $this->indexExists('page_translations', 'page_translations_published_at_index')) {
                $table->index('published_at', 'page_translations_published_at_index');
            }
        });

        Schema::table('page_views', function (Blueprint $table) {
            if (! $this->indexExists('page_views', 'page_views_ip_hash_index')) {
                $table->index('ip_hash', 'page_views_ip_hash_index');
            }
            if (! $this->indexExists('page_views', 'page_views_time_on_page_index')) {
                $table->index('time_on_page', 'page_views_time_on_page_index');
            }
            if (! $this->indexExists('page_views', 'page_views_bounced_index')) {
                $table->index('bounced', 'page_views_bounced_index');
            }
        });

        Schema::table('page_preview_tokens', function (Blueprint $table) {
            if (! $this->indexExists('page_preview_tokens', 'page_preview_tokens_token_index')) {
                $table->index('token', 'page_preview_tokens_token_index');
            }
            if (! $this->indexExists('page_preview_tokens', 'page_preview_tokens_expires_at_index')) {
                $table->index('expires_at', 'page_preview_tokens_expires_at_index');
            }
        });

        Schema::table('page_locks', function (Blueprint $table) {
            if (! $this->indexExists('page_locks', 'page_locks_expires_at_index')) {
                $table->index('expires_at', 'page_locks_expires_at_index');
            }
        });

        Schema::table('page_categories', function (Blueprint $table) {
            if (! $this->indexExists('page_categories', 'page_categories_is_active_index')) {
                $table->index('is_active', 'page_categories_is_active_index');
            }
            if (! $this->indexExists('page_categories', 'page_categories_sort_order_index')) {
                $table->index('sort_order', 'page_categories_sort_order_index');
            }
        });

        Schema::table('page_approvals', function (Blueprint $table) {
            if (! $this->indexExists('page_approvals', 'page_approvals_status_index')) {
                $table->index('status', 'page_approvals_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('pages_pending_approval_index');
            $table->dropIndex('pages_seo_noindex_index');
        });

        Schema::table('page_translations', function (Blueprint $table) {
            $table->dropIndex('page_translations_slug_index');
            $table->dropIndex('page_translations_status_index');
            $table->dropIndex('page_translations_published_at_index');
        });

        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex('page_views_ip_hash_index');
            $table->dropIndex('page_views_time_on_page_index');
            $table->dropIndex('page_views_bounced_index');
        });

        Schema::table('page_preview_tokens', function (Blueprint $table) {
            $table->dropIndex('page_preview_tokens_token_index');
            $table->dropIndex('page_preview_tokens_expires_at_index');
        });

        Schema::table('page_locks', function (Blueprint $table) {
            $table->dropIndex('page_locks_expires_at_index');
        });

        Schema::table('page_categories', function (Blueprint $table) {
            $table->dropIndex('page_categories_is_active_index');
            $table->dropIndex('page_categories_sort_order_index');
        });

        Schema::table('page_approvals', function (Blueprint $table) {
            $table->dropIndex('page_approvals_status_index');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::select(
            "SHOW INDEX FROM {$table} WHERE Key_name = ?",
            [$index]
        ) !== [];
    }
};
