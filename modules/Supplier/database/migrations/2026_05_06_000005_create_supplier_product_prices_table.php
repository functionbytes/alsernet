<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema derived from PriceSyncAgent + SupplierProductPriceObserver expectations:
 *  - cost, discount1, discount2 → final_cost (calculated)
 *  - effective_date for the price activation date
 *  - is_active / is_current flags
 *  - erp_price_id / erp_artiprov_id mapping
 *  - last_synced_at + erp_updated_at for change detection
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_product_prices')) {
            return;
        }

        Schema::create('supplier_product_prices', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();

            // Relationships
            $table->unsignedBigInteger('provider_product_id')->index();

            // ERP mapping
            $table->unsignedBigInteger('erp_price_id')->nullable()->index();
            $table->unsignedBigInteger('erp_artiprov_id')->nullable()->index();

            // Pricing
            $table->decimal('cost', 12, 4)->default(0);
            $table->decimal('discount1', 6, 3)->default(0);
            $table->decimal('discount2', 6, 3)->default(0);
            $table->decimal('final_cost', 12, 4)->default(0);

            // Lifecycle
            $table->date('effective_date')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_current')->default(false)->index();

            // Sync tracking
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('erp_updated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['provider_product_id', 'is_current', 'effective_date'], 'idx_supplier_product_prices_pp_current_date');
            $table->index(['is_active', 'last_synced_at'], 'idx_supplier_product_prices_active_synced');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_product_prices');
    }
};
