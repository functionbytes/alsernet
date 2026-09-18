<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds foreign key constraints for columns that hold relational ids but were
 * never enforced. Several original migrations used unsignedBigInteger()->cascadeOnDelete()
 * without constrained(), which is a no-op in Laravel. Orphaned rows are nulled out
 * (nullable columns) before each constraint is created.
 */
return new class extends Migration
{
    /**
     * Foreign keys to add: column => [referenced table, on-delete action].
     *
     * @var array<string, array<string, array{0: string, 1: string}>>
     */
    private array $foreignKeys = [
        'supplier_extraction_results' => [
            'supplier_id' => ['suppliers', 'null'],
        ],
        'supplier_extraction_batches' => [
            'supplier_id' => ['suppliers', 'null'],
        ],
        'supplier_ai_costs' => [
            'supplier_id' => ['suppliers', 'null'],
            'content_id' => ['supplier_ai_contents', 'null'],
        ],
        'supplier_sync_failures' => [
            'resolved_by_user_id' => ['users', 'null'],
        ],
        'supplier_sync_schedules' => [
            'last_batch_id' => ['supplier_sync_batches', 'null'],
        ],
        'supplier_automation_executions' => [
            'supplier_id' => ['suppliers', 'null'],
        ],
        'supplier_prompts' => [
            'supplier_id' => ['suppliers', 'null'],
            'category_id' => ['supplier_categories', 'null'],
        ],
        'supplier_source_files' => [
            'supplier_id' => ['suppliers', 'cascade'],
        ],
        'supplier_product_prices' => [
            'provider_product_id' => ['supplier_products', 'cascade'],
        ],
    ];

    public function up(): void
    {
        // supplier_prompts.category_id is unsignedInteger but supplier_categories.id is bigint.
        // Widen it before adding the FK so the column types match.
        if (Schema::hasColumn('supplier_prompts', 'category_id')) {
            Schema::table('supplier_prompts', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->change();
            });
        }

        foreach ($this->foreignKeys as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => [$referencedTable, $onDelete]) {
                if (! Schema::hasTable($referencedTable) || ! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $constraint = "{$table}_{$column}_foreign";

                if ($this->foreignKeyExists($constraint)) {
                    continue;
                }

                $this->cleanOrphans($table, $column, $referencedTable, $onDelete);

                Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $onDelete) {
                    $foreign = $blueprint->foreign($column)->references('id')->on($referencedTable);

                    if ($onDelete === 'cascade') {
                        $foreign->cascadeOnDelete();
                    } else {
                        $foreign->nullOnDelete();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->foreignKeys as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($columns) as $column) {
                $constraint = "{$table}_{$column}_foreign";

                if (! $this->foreignKeyExists($constraint)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropForeign([$column]);
                });
            }
        }

        if (Schema::hasColumn('supplier_prompts', 'category_id')) {
            Schema::table('supplier_prompts', function (Blueprint $table) {
                $table->unsignedInteger('category_id')->nullable()->change();
            });
        }
    }

    private function cleanOrphans(string $table, string $column, string $referencedTable, string $onDelete): void
    {
        $orphanCount = DB::table($table)
            ->leftJoin($referencedTable, "{$table}.{$column}", '=', "{$referencedTable}.id")
            ->whereNotNull("{$table}.{$column}")
            ->whereNull("{$referencedTable}.id")
            ->count();

        if ($orphanCount === 0) {
            return;
        }

        if ($onDelete === 'cascade') {
            // NOT NULL column: orphans cannot be nulled, delete them instead.
            DB::statement(
                "DELETE t FROM {$table} t
                 LEFT JOIN {$referencedTable} r ON r.id = t.{$column}
                 WHERE t.{$column} IS NOT NULL AND r.id IS NULL"
            );

            return;
        }

        DB::statement(
            "UPDATE {$table} t
             LEFT JOIN {$referencedTable} r ON r.id = t.{$column}
             SET t.{$column} = NULL
             WHERE t.{$column} IS NOT NULL AND r.id IS NULL"
        );
    }

    private function foreignKeyExists(string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
