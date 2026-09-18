<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_ai_contents')) {
            try {
                Schema::table('supplier_ai_contents', function (Blueprint $table) {
                    $table->index(['status', 'created_at'], 'idx_ai_contents_status_created_at');
                });
            } catch (Throwable $e) {
                if (! str_contains($e->getMessage(), 'Duplicate key') && ! str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        }

        if (Schema::hasTable('supplier_products')) {
            try {
                Schema::table('supplier_products', function (Blueprint $table) {
                    $table->index(['supplier_id', 'available'], 'idx_products_supplier_available');
                });
            } catch (Throwable $e) {
                if (! str_contains($e->getMessage(), 'Duplicate key') && ! str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        }

        if (Schema::hasTable('supplier_sources')) {
            try {
                Schema::table('supplier_sources', function (Blueprint $table) {
                    $table->index(['supplier_id', 'is_active'], 'idx_sources_supplier_active');
                });
            } catch (Throwable $e) {
                if (! str_contains($e->getMessage(), 'Duplicate key') && ! str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            $table->dropIndex('idx_ai_contents_status_created_at');
        });

        Schema::table('supplier_products', function (Blueprint $table) {
            $table->dropIndex('idx_products_supplier_available');
        });

        Schema::table('supplier_sources', function (Blueprint $table) {
            $table->dropIndex('idx_sources_supplier_active');
        });
    }
};
