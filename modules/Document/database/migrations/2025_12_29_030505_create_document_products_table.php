<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Productos asociados a documentos en validación.
     * Puede ser necesario validar documentos específicos para ciertos productos.
     */
    public function up(): void
    {
        Schema::create('document_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')
                ->nullable()
                ->comment('ID del producto (PrestaShop)');
            $table->string('product_name')->comment('Nombre del producto');
            $table->string('product_reference')
                ->nullable()
                ->comment('Referencia/SKU del producto');
            $table->unsignedInteger('quantity')->default(1)->comment('Cantidad');
            $table->decimal('price', 10, 2)
                ->nullable()
                ->comment('Precio del producto');
            $table->timestamps();

            $table->index('document_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_products');
    }
};
