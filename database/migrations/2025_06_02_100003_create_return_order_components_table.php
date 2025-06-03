<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_order_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignId('component_id')->constrained('product_components')->onDelete('cascade');
            $table->integer('quantity_required'); // Cantidad requerida
            $table->integer('quantity_reserved')->default(0); // Cantidad reservada
            $table->integer('quantity_allocated')->default(0); // Cantidad asignada/separada
            $table->integer('quantity_shipped')->default(0); // Cantidad enviada
            $table->integer('quantity_missing')->default(0); // Cantidad faltante
            $table->string('status')->default('pending'); // pending, reserved, allocated, partial, shipped, missing
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->decimal('deduction_amount', 10, 2)->default(0.00); // Monto deducido por faltante
            $table->string('deduction_type')->nullable(); // percentage, fixed_amount
            $table->decimal('deduction_applied', 10, 2)->default(0.00); // Deducción aplicada
            $table->boolean('is_essential')->default(true); // Si es esencial para el funcionamiento
            $table->boolean('can_substitute')->default(false); // Si se puede sustituir
            $table->foreignId('substitute_component_id')->nullable()->constrained('product_components')->onDelete('set null');
            $table->integer('substitute_quantity')->default(0);
            $table->json('serial_numbers')->nullable(); // Números de serie asignados
            $table->string('batch_number')->nullable(); // Número de lote
            $table->date('expected_date')->nullable(); // Fecha esperada de disponibilidad
            $table->text('notes')->nullable();
            $table->json('shipment_tracking')->nullable(); // Información de envíos parciales
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['order_id', 'status']);
            $table->index(['component_id', 'status']);
            $table->index(['order_item_id', 'is_essential']);
            $table->index(['status', 'expected_date']);
            $table->index('substitute_component_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_order_components');
    }
};
