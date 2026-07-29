<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add foreign key constraints that were declared in comments but never enforced
 * by the original migrations, plus restore the unique constraint on supplier_prompts.uid
 * that was lost when the column was changed in 2026_03_24_000001.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. supplier_credentials.supplier_id → suppliers.id
        if (Schema::hasTable('supplier_credentials') && Schema::hasTable('suppliers')) {
            // Null-out orphaned references before adding the constraint.
            DB::statement(
                'UPDATE supplier_credentials sc
                 LEFT JOIN suppliers s ON s.id = sc.supplier_id
                 SET sc.supplier_id = NULL
                 WHERE sc.supplier_id IS NOT NULL AND s.id IS NULL'
            );

            Schema::table('supplier_credentials', function (Blueprint $table) {
                $table->foreign('supplier_id', 'fk_supplier_credentials_supplier')
                    ->references('id')
                    ->on('suppliers')
                    ->nullOnDelete();
            });
        }

        // 2. supplier_ai_contents.supplier_product_id → supplier_products.id
        if (Schema::hasTable('supplier_ai_contents') && Schema::hasTable('supplier_products')) {
            DB::statement(
                'UPDATE supplier_ai_contents ac
                 LEFT JOIN supplier_products p ON p.id = ac.supplier_product_id
                 SET ac.supplier_product_id = NULL
                 WHERE ac.supplier_product_id IS NOT NULL AND p.id IS NULL'
            );

            Schema::table('supplier_ai_contents', function (Blueprint $table) {
                $table->foreign('supplier_product_id', 'fk_supplier_ai_contents_product')
                    ->references('id')
                    ->on('supplier_products')
                    ->nullOnDelete();
            });
        }

        // 3. supplier_sync_conflicts.resolved_by_user_id → users.id
        if (Schema::hasTable('supplier_sync_conflicts') && Schema::hasTable('users')) {
            DB::statement(
                'UPDATE supplier_sync_conflicts c
                 LEFT JOIN users u ON u.id = c.resolved_by_user_id
                 SET c.resolved_by_user_id = NULL
                 WHERE c.resolved_by_user_id IS NOT NULL AND u.id IS NULL'
            );

            Schema::table('supplier_sync_conflicts', function (Blueprint $table) {
                $table->foreign('resolved_by_user_id', 'fk_supplier_sync_conflicts_user')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        // 4. supplier_prompts.uid: restore unique index lost by the previous change()
        if (Schema::hasTable('supplier_prompts')) {
            $existing = collect(DB::select('SHOW INDEX FROM supplier_prompts'))
                ->pluck('Key_name')
                ->unique()
                ->toArray();

            if (! in_array('supplier_prompts_uid_unique', $existing, true)) {
                Schema::table('supplier_prompts', function (Blueprint $table) {
                    $table->unique('uid', 'supplier_prompts_uid_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_credentials')) {
            Schema::table('supplier_credentials', function (Blueprint $table) {
                $table->dropForeign('fk_supplier_credentials_supplier');
            });
        }

        if (Schema::hasTable('supplier_ai_contents')) {
            Schema::table('supplier_ai_contents', function (Blueprint $table) {
                $table->dropForeign('fk_supplier_ai_contents_product');
            });
        }

        if (Schema::hasTable('supplier_sync_conflicts')) {
            Schema::table('supplier_sync_conflicts', function (Blueprint $table) {
                $table->dropForeign('fk_supplier_sync_conflicts_user');
            });
        }

        if (Schema::hasTable('supplier_prompts')) {
            Schema::table('supplier_prompts', function (Blueprint $table) {
                $table->dropUnique('supplier_prompts_uid_unique');
            });
        }
    }
};
