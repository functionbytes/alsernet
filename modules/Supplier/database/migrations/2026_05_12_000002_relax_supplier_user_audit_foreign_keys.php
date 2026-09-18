<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The created_by / updated_by audit columns on supplier source configurations and
 * templates point to users with the default RESTRICT rule, which blocks deleting any
 * user who ever created one of those records. Switch them to ON DELETE SET NULL.
 * supplier_prompts.source_id used ON DELETE CASCADE, meaning deleting a source wiped
 * its prompts; switch it to SET NULL so global/supplier prompts survive.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $userAuditColumns = [
        'supplier_source_configurations' => ['created_by', 'updated_by'],
        'supplier_source_templates' => ['created_by'],
    ];

    public function up(): void
    {
        foreach ($this->userAuditColumns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->unsignedBigInteger($column)->nullable()->change();
                });

                $this->recreateForeignKey($table, $column, 'users', 'null');
            }
        }

        if (Schema::hasTable('supplier_prompts') && Schema::hasColumn('supplier_prompts', 'source_id')) {
            Schema::table('supplier_prompts', function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('source_id')->nullable()->change();
            });

            $this->recreateForeignKey('supplier_prompts', 'source_id', 'supplier_sources', 'null');
        }
    }

    public function down(): void
    {
        foreach ($this->userAuditColumns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $this->recreateForeignKey($table, $column, 'users', 'restrict');
            }
        }

        if (Schema::hasTable('supplier_prompts') && Schema::hasColumn('supplier_prompts', 'source_id')) {
            $this->recreateForeignKey('supplier_prompts', 'source_id', 'supplier_sources', 'cascade');
        }
    }

    private function recreateForeignKey(string $table, string $column, string $referencedTable, string $onDelete): void
    {
        $constraint = "{$table}_{$column}_foreign";

        if ($this->foreignKeyExists($constraint)) {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropForeign([$column]);
            });
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $onDelete) {
            $foreign = $blueprint->foreign($column)->references('id')->on($referencedTable);

            match ($onDelete) {
                'cascade' => $foreign->cascadeOnDelete(),
                'null' => $foreign->nullOnDelete(),
                default => $foreign->restrictOnDelete(),
            };
        });
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
