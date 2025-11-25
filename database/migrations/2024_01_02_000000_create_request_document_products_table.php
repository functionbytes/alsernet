<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_document_products', function (Blueprint $table) {
            $table->id();

            // Foreign key al documento
            $table->unsignedBigInteger('document_id');
            $table->foreign('document_id')
                ->references('id')
                ->on('request_documents')
                ->onDelete('cascade');

            // Datos del producto (desnormalizados)
            $table->unsignedInteger('product_id')->nullable()
                ->comment('Prestashop product ID');
            $table->string('product_name', 255)
                ->comment('Product name at time of document creation');
            $table->string('product_reference', 64)->nullable()
                ->comment('Product reference code');
            $table->integer('quantity')
                ->default(1)
                ->comment('Quantity ordered');
            $table->decimal('price', 12, 2)->nullable()
                ->comment('Unit price at time of document creation');

            $table->timestamps();

            // Índices
            $table->index('document_id');
            $table->index('product_id');
            $table->index('product_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_products');
    }
};
