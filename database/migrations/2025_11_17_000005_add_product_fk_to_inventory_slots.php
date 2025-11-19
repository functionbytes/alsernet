<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the product_id foreign key constraint to warehouse_inventory_slots table.
     * This is in a separate migration to avoid constraint conflicts during initial creation.
     */
    public function up(): void
    {
        if (Schema::hasTable('warehouse_inventory_slots') && Schema::hasTable('products')) {
            Schema::table('warehouse_inventory_slots', function (Blueprint $table) {
                // Check if the foreign key doesn't already exist
                if (!Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('warehouse_inventory_slots')) {
                    try {
                        $table->foreign('product_id')
                            ->references('id')
                            ->on('products')
                            ->onDelete('set null');
                    } catch (\Exception $e) {
                        // Foreign key might already exist or there's an issue - continue
                        \Log::warning('Could not add product_id FK: ' . $e->getMessage());
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('warehouse_inventory_slots')) {
            Schema::table('warehouse_inventory_slots', function (Blueprint $table) {
                try {
                    $table->dropForeign(['product_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist - continue
                    \Log::warning('Could not drop product_id FK: ' . $e->getMessage());
                }
            });
        }
    }
};
