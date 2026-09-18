<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalises loosely-typed columns:
 *  - memory metrics stored as double → decimal(12,3) nullable for stable reporting.
 *  - supplier_ai_budgets.notify_emails stored as text → json nullable.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $memoryColumns = [
        'supplier_sync_audit' => ['memory_mb', 'peak_memory_mb'],
        'supplier_sync_statuses' => ['memory_used_mb'],
    ];

    public function up(): void
    {
        foreach ($this->memoryColumns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->decimal($column, 12, 3)->nullable()->change();
                });
            }
        }

        if (Schema::hasTable('supplier_ai_budgets') && Schema::hasColumn('supplier_ai_budgets', 'notify_emails')) {
            $invalidJsonRows = DB::table('supplier_ai_budgets')
                ->whereNotNull('notify_emails')
                ->where('notify_emails', '!=', '')
                ->whereRaw('JSON_VALID(notify_emails) = 0')
                ->count();

            if ($invalidJsonRows > 0) {
                throw new RuntimeException(
                    "Cannot convert supplier_ai_budgets.notify_emails to json: {$invalidJsonRows} row(s) hold non-JSON text. Clean them first."
                );
            }

            Schema::table('supplier_ai_budgets', function (Blueprint $blueprint) {
                $blueprint->json('notify_emails')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->memoryColumns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->double($column)->nullable()->change();
                });
            }
        }

        if (Schema::hasTable('supplier_ai_budgets') && Schema::hasColumn('supplier_ai_budgets', 'notify_emails')) {
            Schema::table('supplier_ai_budgets', function (Blueprint $blueprint) {
                $blueprint->text('notify_emails')->nullable()->change();
            });
        }
    }
};
