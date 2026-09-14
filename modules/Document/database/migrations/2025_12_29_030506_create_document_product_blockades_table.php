<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Productos o atributos bloqueados que requieren validación de documentos específicos.
     * Ejemplo: productos de venta restringida, armas, etc. que requieren documentación.
     */
    public function up(): void
    {
        Schema::create('document_product_blockades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id')
                ->nullable()
                ->comment('Source ID from PrestaShop');
            $table->unsignedBigInteger('product_id')
                ->nullable()
                ->comment('ID del producto bloqueado (PrestaShop)');
            $table->unsignedBigInteger('product_attribute_id')
                ->nullable()
                ->comment('ID del atributo de producto bloqueado (PrestaShop)');
            $table->foreignId('document_type_id')
                ->nullable()
                ->constrained('document_types')
                ->nullOnDelete()
                ->comment('Tipo de documento requerido para este producto');
            $table->timestamps();

            $table->index('product_id');
            $table->index('product_attribute_id');
            $table->index('document_type_id');
            $table->unique(['product_id', 'product_attribute_id', 'document_type_id'], 'unique_blockade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_product_blockades');
    }
};
