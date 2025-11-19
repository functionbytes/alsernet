<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained('product_components')->onDelete('cascade');
            $table->string('movement_type'); // in, out, adjustment, transfer, reservation, release
            $table->string('reference_type')->nullable(); // order, purchase, adjustment, transfer, etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID de la referencia
            $table->integer('quantity'); // Cantidad (positiva o negativa)
            $table->integer('stock_before'); // Stock antes del movimiento
            $table->integer('stock_after'); // Stock después del movimiento
            $table->decimal('unit_cost', 10, 2)->nullable(); // Costo unitario del movimiento
            $table->decimal('total_cost', 10, 2)->nullable(); // Costo total del movimiento
            $table->string('reason')->nullable(); // Razón del movimiento
            $table->text('notes')->nullable();
            $table->string('batch_number')->nullable(); // Número de lote
            $table->date('expiry_date')->nullable(); // Fecha de vencimiento si aplica
            $table->json('serial_numbers')->nullable(); // Números de serie involucrados
            $table->string('location_from')->nullable(); // Ubicación origen
            $table->string('location_to')->nullable(); // Ubicación destino
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Usuario que hizo el movimiento
            $table->timestamp('movement_date')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['component_id', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['movement_type', 'movement_date']);
            $table->index('user_id');
            $table->index('batch_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_stock_movements');
    }
};
