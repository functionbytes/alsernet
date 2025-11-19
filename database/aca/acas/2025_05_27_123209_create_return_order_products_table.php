<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnOrderProductsTable extends Migration
{
    public function up()
    {
        Schema::create('return_order_products', function (Blueprint $table) {
            $table->id(); // equivale a bigIncrements('id')

            // Relación con return_orders
            $table->foreignId('order_id')
                ->constrained('return_orders')
                ->onDelete('cascade');

            // Información del producto desde ERP
            $table->string('erp_product_id')->nullable();
            $table->string('product_code')->nullable();
            $table->string('product_name');
            $table->text('product_description')->nullable();

            // Cantidades y precios
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);

            // Información adicional
            $table->string('catalog_id')->nullable();
            $table->boolean('is_returnable')->default(true);
            $table->boolean('is_gift')->default(false);
            $table->decimal('weight', 8, 3)->nullable();

            // Datos completos de la línea ERP en JSON
            $table->json('erp_line_data')->nullable();

            $table->timestamps();

            // Índices
            $table->index('product_code');
            $table->index('erp_product_id');
            $table->index('is_returnable');
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_order_products');
    }
}
