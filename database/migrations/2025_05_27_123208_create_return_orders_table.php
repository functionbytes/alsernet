<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('return_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Información básica del pedido
            $table->string('erp_order_id')->unique(); // ID del pedido en ERP
            $table->string('order_number')->nullable(); // Número de pedido visible

            // Información del cliente
            $table->string('customer_id')->nullable(); // ID del cliente en ERP
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_cif')->nullable();

            // Información del pedido
            $table->date('order_date');
            $table->decimal('total_amount', 10, 2);
            $table->string('status', 50)->default('active');
            $table->string('erp_status_id')->nullable();
            $table->string('erp_status_description')->nullable();

            // Información de pago
            $table->string('payment_method_id')->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();

            // Información del almacén
            $table->string('warehouse_id')->nullable();
            $table->string('warehouse_description')->nullable();

            // Información de envío
            $table->text('shipping_address')->nullable();
            $table->string('shipping_province')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->decimal('shipping_cost', 8, 2)->default(0);

            // Información adicional
            $table->string('series_description')->nullable();

            // Datos completos del ERP en JSON
            $table->json('erp_data')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['erp_order_id']);
            $table->index(['customer_id']);
            $table->index(['customer_email']);
            $table->index(['order_date']);
            $table->index(['status']);
            $table->index(['erp_status_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_orders');
    }
}
