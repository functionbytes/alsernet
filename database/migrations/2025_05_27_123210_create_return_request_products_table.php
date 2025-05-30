<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnRequestProductsTable extends Migration
{
    public function up()
    {
        Schema::create('return_request_products', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Relaciones
            $table->unsignedBigInteger('return_id');
            $table->foreign('return_id')
                ->references('id')
                ->on('return_requests')
                ->onDelete('cascade');

            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id') // ✅ Corregido aquí
            ->references('id')
                ->on('return_order_products')
                ->onDelete('cascade');

            // Información del producto (copia para histórico)
            $table->string('product_code')->nullable();
            $table->string('product_name');

            // Cantidades
            $table->decimal('quantity', 10, 2);
            $table->decimal('approved_quantity', 10, 2)->nullable();

            // Precios
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('refund_amount', 10, 2)->nullable();

            // Información de la devolución
            $table->unsignedBigInteger('reason_id')->nullable();
            $table->foreign('reason_id')
                ->references('id')
                ->on('return_reasons');

            $table->enum('return_condition', ['new', 'good', 'fair', 'poor', 'damaged'])
                ->default('good');

            $table->text('notes')->nullable();

            // Estado de aprobación
            $table->boolean('is_approved')->nullable();
            $table->boolean('replacement_requested')->default(false);

            $table->timestamps();

            // Índices
            $table->index('return_id');
            $table->index('product_id');
            $table->index('product_code');
            $table->index('is_approved');
            $table->index('return_condition');

            // Constraint para evitar duplicados
            $table->unique(['return_id', 'product_id'], 'unique_return_product');
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_request_products');
    }
}
