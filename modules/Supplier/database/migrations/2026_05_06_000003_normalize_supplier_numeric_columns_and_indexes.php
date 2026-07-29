<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalise duration columns from float to decimal(12,3) for accurate
 * reporting and add the composite indexes hot paths rely on:
 *
 *  - supplier_products(supplier_id, available, updated_at, last_sync_at)
 *    — used by ProductSyncAgent's cursor query.
 *  - supplier_ai_content_versions(content_id, version_number)
 *    — used by AiContentVersionObserver to compute the next version.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_sync_batches') && Schema::hasColumn('supplier_sync_batches', 'duration_seconds')) {
            Schema::table('supplier_sync_batches', function (Blueprint $table) {
                $table->decimal('duration_seconds', 12, 3)->nullable()->change();
            });
        }

        if (Schema::hasTable('supplier_sync_statuses') && Schema::hasColumn('supplier_sync_statuses', 'elapsed_seconds')) {
            Schema::table('supplier_sync_statuses', function (Blueprint $table) {
                $table->decimal('elapsed_seconds', 12, 3)->nullable()->change();
            });
        }

        if (Schema::hasTable('supplier_sync_audit') && Schema::hasColumn('supplier_sync_audit', 'elapsed_seconds')) {
            Schema::table('supplier_sync_audit', function (Blueprint $table) {
                $table->decimal('elapsed_seconds', 12, 3)->default(0)->change();
            });
        }

        if (Schema::hasTable('supplier_products')) {
            $existing = collect(DB::select('SHOW INDEX FROM supplier_products'))
                ->pluck('Key_name')
                ->unique()
                ->toArray();

            if (! in_array('idx_supplier_products_sync_cursor', $existing, true)) {
                Schema::table('supplier_products', function (Blueprint $table) {
                    $table->index(
                        ['supplier_id', 'available', 'updated_at', 'last_sync_at'],
                        'idx_supplier_products_sync_cursor'
                    );
                });
            }
        }

        if (Schema::hasTable('supplier_ai_content_versions')) {
            $existing = collect(DB::select('SHOW INDEX FROM supplier_ai_content_versions'))
                ->pluck('Key_name')
                ->unique()
                ->toArray();

            if (! in_array('idx_ai_content_versions_content_version', $existing, true)) {
                Schema::table('supplier_ai_content_versions', function (Blueprint $table) {
                    $table->index(
                        ['content_id', 'version_number'],
                        'idx_ai_content_versions_content_version'
                    );
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_sync_batches') && Schema::hasColumn('supplier_sync_batches', 'duration_seconds')) {
            Schema::table('supplier_sync_batches', function (Blueprint $table) {
                $table->float('duration_seconds')->nullable()->change();
            });
        }

        if (Schema::hasTable('supplier_sync_statuses') && Schema::hasColumn('supplier_sync_statuses', 'elapsed_seconds')) {
            Schema::table('supplier_sync_statuses', function (Blueprint $table) {
                $table->float('elapsed_seconds')->nullable()->change();
            });
        }

        if (Schema::hasTable('supplier_sync_audit') && Schema::hasColumn('supplier_sync_audit', 'elapsed_seconds')) {
            Schema::table('supplier_sync_audit', function (Blueprint $table) {
                $table->double('elapsed_seconds')->default(0)->change();
            });
        }

        if (Schema::hasTable('supplier_products')) {
            Schema::table('supplier_products', function (Blueprint $table) {
                $table->dropIndex('idx_supplier_products_sync_cursor');
            });
        }

        if (Schema::hasTable('supplier_ai_content_versions')) {
            Schema::table('supplier_ai_content_versions', function (Blueprint $table) {
                $table->dropIndex('idx_ai_content_versions_content_version');
            });
        }
    }
};
