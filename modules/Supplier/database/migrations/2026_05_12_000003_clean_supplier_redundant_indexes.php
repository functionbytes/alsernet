<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes indexes that are exact duplicates or fully covered as a prefix by a
 * composite index, and adds the index AiContent::hasDuplicate() relies on
 * (WHERE content_hash = ?).
 */
return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $redundantIndexes = [
        // Exact duplicate of supplier_sources_supplier_id_is_active_index.
        'supplier_sources' => ['idx_sources_supplier_active'],
        // status alone is the prefix of idx_ai_contents_status_created_at.
        'supplier_ai_contents' => ['supplier_ai_contents_status_index'],
        // supplier_id alone is the prefix of idx_products_supplier_available and
        // idx_supplier_products_sync_cursor; one of those backs the FK.
        'supplier_products' => ['supplier_products_supplier_id_index'],
    ];

    public function up(): void
    {
        foreach ($this->redundantIndexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $index) {
                if (! $this->indexExists($table, $index)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    $blueprint->dropIndex($index);
                });
            }
        }

        if (Schema::hasTable('supplier_ai_contents')
            && Schema::hasColumn('supplier_ai_contents', 'content_hash')
            && ! $this->indexExists('supplier_ai_contents', 'idx_ai_contents_content_hash')) {
            Schema::table('supplier_ai_contents', function (Blueprint $blueprint) {
                $blueprint->index('content_hash', 'idx_ai_contents_content_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_ai_contents') && $this->indexExists('supplier_ai_contents', 'idx_ai_contents_content_hash')) {
            Schema::table('supplier_ai_contents', function (Blueprint $blueprint) {
                $blueprint->dropIndex('idx_ai_contents_content_hash');
            });
        }

        if (Schema::hasTable('supplier_sources') && ! $this->indexExists('supplier_sources', 'idx_sources_supplier_active')) {
            Schema::table('supplier_sources', function (Blueprint $blueprint) {
                $blueprint->index(['supplier_id', 'is_active'], 'idx_sources_supplier_active');
            });
        }

        if (Schema::hasTable('supplier_ai_contents') && ! $this->indexExists('supplier_ai_contents', 'supplier_ai_contents_status_index')) {
            Schema::table('supplier_ai_contents', function (Blueprint $blueprint) {
                $blueprint->index('status', 'supplier_ai_contents_status_index');
            });
        }

        if (Schema::hasTable('supplier_products') && ! $this->indexExists('supplier_products', 'supplier_products_supplier_id_index')) {
            Schema::table('supplier_products', function (Blueprint $blueprint) {
                $blueprint->index('supplier_id', 'supplier_products_supplier_id_index');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }
};
