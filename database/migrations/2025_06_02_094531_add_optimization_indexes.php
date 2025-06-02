<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class AddOptimizationIndexes extends Migration
{
    public function up()
    {
        // Índices para return_requests
        Schema::table('return_requests', function (Blueprint $table) {
            $table->index(['status_id', 'created_at']);
            $table->index(['customer_id', 'status_id']);
            $table->index(['order_id', 'status_id']);
            $table->index('logistics_mode');
        });

        // Índices para return_request_products
        Schema::table('return_request_products', function (Blueprint $table) {
            $table->index(['request_id', 'is_approved']);
            $table->index(['product_id', 'request_id']);
        });

        // Índices para return_orders
        Schema::table('return_orders', function (Blueprint $table) {
            if (!Schema::hasIndex('return_orders', 'return_orders_erp_order_id_index')) {
                $table->index('erp_order_id');
            }
            if (!Schema::hasIndex('return_orders', 'return_orders_order_number_index')) {
                $table->index('order_number');
            }
            $table->index(['customer_id', 'order_date']);
        });
    }

    public function down()
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropIndex(['status_id', 'created_at']);
            $table->dropIndex(['customer_id', 'status_id']);
            $table->dropIndex(['order_id', 'status_id']);
            $table->dropIndex(['logistics_mode']);
        });

        Schema::table('return_request_products', function (Blueprint $table) {
            $table->dropIndex(['request_id', 'is_approved']);
            $table->dropIndex(['product_id', 'request_id']);
        });

        Schema::table('return_orders', function (Blueprint $table) {
            $table->dropIndex(['erp_order_id']);
            $table->dropIndex(['order_number']);
            $table->dropIndex(['customer_id', 'order_date']);
        });
    }
}

