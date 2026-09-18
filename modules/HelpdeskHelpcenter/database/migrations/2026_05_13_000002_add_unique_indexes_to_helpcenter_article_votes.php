<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    private const TABLE = 'helpdesk_helpcenter_article_votes';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable(self::TABLE)) {
            return;
        }

        $indexes = collect($schema->getIndexes(self::TABLE))->pluck('name')->all();

        if (in_array('hcv_article_cookie_uq', $indexes, true) && in_array('hcv_article_iphash_uq', $indexes, true)) {
            return;
        }

        // Step 1: add unique indexes first so the FK on article_id always has a supporting index.
        $schema->table(self::TABLE, function (Blueprint $table) use ($indexes) {
            if (! in_array('hcv_article_cookie_uq', $indexes, true)) {
                $table->unique(['article_id', 'cookie_id'], 'hcv_article_cookie_uq');
            }

            if (! in_array('hcv_article_iphash_uq', $indexes, true)) {
                $table->unique(['article_id', 'ip_hash'], 'hcv_article_iphash_uq');
            }
        });

        // Step 2: drop old non-unique composite indexes now that the FK is covered by the uniques above.
        $indexes = collect($schema->getIndexes(self::TABLE))->pluck('name')->all();

        $schema->table(self::TABLE, function (Blueprint $table) use ($indexes) {
            if (in_array(self::TABLE.'_article_id_cookie_id_index', $indexes, true)) {
                $table->dropIndex(['article_id', 'cookie_id']);
            }

            if (in_array(self::TABLE.'_article_id_ip_hash_index', $indexes, true)) {
                $table->dropIndex(['article_id', 'ip_hash']);
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable(self::TABLE)) {
            return;
        }

        $indexes = collect($schema->getIndexes(self::TABLE))->pluck('name')->all();

        $schema->table(self::TABLE, function (Blueprint $table) use ($indexes) {
            if (in_array('hcv_article_cookie_uq', $indexes, true)) {
                $table->dropUnique('hcv_article_cookie_uq');
            }

            if (in_array('hcv_article_iphash_uq', $indexes, true)) {
                $table->dropUnique('hcv_article_iphash_uq');
            }

            if (! in_array(self::TABLE.'_article_id_cookie_id_index', $indexes, true)) {
                $table->index(['article_id', 'cookie_id']);
            }

            if (! in_array(self::TABLE.'_article_id_ip_hash_index', $indexes, true)) {
                $table->index(['article_id', 'ip_hash']);
            }
        });
    }
};
