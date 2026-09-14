<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'customer_cellphone_normalized')) {
                $table->string('customer_cellphone_normalized', 9)->nullable()->after('customer_cellphone');
            }

            if (! $this->hasIndex('documents', 'documents_customer_cellphone_normalized_index')) {
                $table->index('customer_cellphone_normalized', 'documents_customer_cellphone_normalized_index');
            }
        });

        // Backfill: replica PhoneMatcher::normalize() (últimos 9 dígitos tras
        // quitar todo lo no-numérico, o NULL si quedan menos de 9). REGEXP_REPLACE
        // no es estándar en sqlite, así que el backfill solo corre en MySQL/MariaDB.
        if (config('database.default') !== 'sqlite') {
            DB::statement("
                UPDATE documents
                SET customer_cellphone_normalized = CASE
                    WHEN CHAR_LENGTH(REGEXP_REPLACE(COALESCE(customer_cellphone, ''), '[^0-9]', '')) >= 9
                    THEN RIGHT(REGEXP_REPLACE(COALESCE(customer_cellphone, ''), '[^0-9]', ''), 9)
                    ELSE NULL
                END
                WHERE customer_cellphone IS NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('documents', 'documents_customer_cellphone_normalized_index');

        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'customer_cellphone_normalized')) {
                $table->dropColumn('customer_cellphone_normalized');
            }
        });
    }

    /**
     * Check if index exists
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        if (config('database.default') === 'sqlite') {
            try {
                $indexes = DB::select(
                    "SELECT name FROM sqlite_master WHERE type='index' AND name=?",
                    [$indexName]
                );

                return ! empty($indexes);
            } catch (Exception $e) {
                return false;
            }
        }

        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");

        return ! empty($indexes);
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->hasIndex($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};
