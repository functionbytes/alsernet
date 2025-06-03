<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('component_shipments')->onDelete('cascade');
            $table->foreignId('order_component_id')->constrained('order_components')->onDelete('cascade');
            $table->foreignId('component_id')->constrained('product_components')->onDelete('cascade');
            $table->integer('quantity_shipped');
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->decimal('weight', 8, 3)->default(0.000);
            $table->json('serial_numbers')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('condition')->default('new'); // new, refurbished, used
            $table->text('quality_notes')->nullable();
            $table->string('package_reference')->nullable(); // Referencia del paquete
            $table->json('packaging_info')->nullable();
            $table->boolean('is_replacement')->default(false);
            $table->boolean('requires_return')->default(false); // Si requiere retorno del componente original
            $table->date('return_deadline')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['shipment_id', 'component_id']);
            $table->index('order_component_id');
            $table->index(['batch_number', 'condition']);
            $table->index(['is_replacement', 'requires_return']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_shipment_items');
    }
};
