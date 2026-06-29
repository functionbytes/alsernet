<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecommerce_orders')) {
            Schema::table('ecommerce_orders', function (Blueprint $table): void {
                if (! Schema::hasIndex('ecommerce_orders', 'eo_customer_status_idx')) {
                    $table->index(['customer_id', 'status'], 'eo_customer_status_idx');
                }
                if (! Schema::hasIndex('ecommerce_orders', 'eo_customer_created_idx')) {
                    $table->index(['customer_id', 'created_at'], 'eo_customer_created_idx');
                }
            });
        }

        if (Schema::hasTable('ecommerce_carts')) {
            Schema::table('ecommerce_carts', function (Blueprint $table): void {
                if (! Schema::hasIndex('ecommerce_carts', 'ec_customer_idx')) {
                    $table->index('customer_id', 'ec_customer_idx');
                }
            });
        }

        if (Schema::hasTable('ecommerce_products')) {
            Schema::table('ecommerce_products', function (Blueprint $table): void {
                if (! Schema::hasIndex('ecommerce_products', 'ep_status_featured_idx')) {
                    $table->index(['status', 'is_featured'], 'ep_status_featured_idx');
                }
                if (! Schema::hasIndex('ecommerce_products', 'ep_status_brand_idx')) {
                    $table->index(['status', 'brand_id'], 'ep_status_brand_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ecommerce_orders')) {
            Schema::table('ecommerce_orders', function (Blueprint $table): void {
                $table->dropIndex('eo_customer_status_idx');
                $table->dropIndex('eo_customer_created_idx');
            });
        }

        if (Schema::hasTable('ecommerce_carts')) {
            Schema::table('ecommerce_carts', function (Blueprint $table): void {
                $table->dropIndex('ec_customer_idx');
            });
        }

        if (Schema::hasTable('ecommerce_products')) {
            Schema::table('ecommerce_products', function (Blueprint $table): void {
                $table->dropIndex('ep_status_featured_idx');
                $table->dropIndex('ep_status_brand_idx');
            });
        }
    }
};
