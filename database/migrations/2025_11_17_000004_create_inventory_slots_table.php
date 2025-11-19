<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de POSICIONES DE INVENTARIO (SLOTS)
     *
     * Representa una posición concreta dentro de una estantería.
     * Ubicación específica: [Stand] → [Cara] → [Nivel] → [Sección]
     *
     * Estructura:
     * - id: Identificador único
     * - uid: UUID universal
     * - stand_id: FK a estantería
     * - face: Cara del stand (left, right, front, back)
     * - level: Nivel (1=arriba, 2, 3=abajo, etc)
     * - section: Sección horizontal (1=izquierda, 2, 3=derecha, etc)
     * - barcode: Código de barras de la posición
     * - product_id: FK a producto (nullable si está vacía)
     * - quantity: Cantidad actual del producto
     * - max_quantity: Máximo permitido
     * - weight_current: Peso actual
     * - weight_max: Peso máximo
     * - is_occupied: Bool (cache para búsquedas rápidas)
     * - last_movement: Timestamp de última operación
     * - timestamps: Auditoría
     */
    public function up(): void
    {
        Schema::create('warehouse_inventory_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uid')->unique()->comment('Universal ID para URLs/APIs');

            // Relaciones
            $table->unsignedBigInteger('stand_id')->comment('ID de la estantería');
            $table->unsignedBigInteger('product_id')->nullable()->comment('ID del producto almacenado');

            // Ubicación
            $table->enum('face', ['left', 'right', 'front', 'back'])
                ->comment('Cara del stand: left, right, front, back');
            $table->integer('level')->comment('Nivel de altura (1=arriba, aumenta hacia abajo)');
            $table->integer('section')->comment('Sección horizontal (1=izquierda, aumenta hacia derecha)');

            // Identificación
            $table->string('barcode', 100)->nullable()->unique()->comment('Código de barras de la posición');

            // Contenido
            $table->integer('quantity')->default(0)->comment('Cantidad actual del producto');
            $table->integer('max_quantity')->nullable()->comment('Máximo permitido para esta posición');

            // Peso
            $table->decimal('weight_current', 8, 2)->default(0)->comment('Peso actual (kg)');
            $table->decimal('weight_max', 8, 2)->nullable()->comment('Peso máximo permitido (kg)');

            // Estado
            $table->boolean('is_occupied')->default(false)->comment('Cache: está ocupada?');
            $table->timestamp('last_movement')->nullable()->comment('Última operación de movimiento');

            // Auditoría
            $table->timestamps();

            // Foreign keys
            $table->foreign('stand_id')
                ->references('id')
                ->on('warehouse_stands')
                ->onDelete('cascade');

            // Note: product_id is nullable - only set when a product is assigned to a slot
            // Foreign key removed to avoid constraint conflicts - will be added via separate migration if needed

            // Índices
            $table->index('stand_id');
            $table->index('product_id');
            $table->index('barcode');
            $table->index('is_occupied');
            $table->index('last_movement');
            // Índice compuesto para búsquedas por posición
            $table->unique(['stand_id', 'face', 'level', 'section']);
            // Índice para búsquedas de disponibilidad
            $table->index(['stand_id', 'is_occupied']);
            $table->index(['stand_id', 'face', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory_slots');
    }
};
