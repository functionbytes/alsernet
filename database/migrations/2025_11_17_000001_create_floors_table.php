<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de PISOS/PLANTAS del almacén
     *
     * Estructura:
     * - id: Identificador único
     * - uid: UUID universal para URLs y APIs
     * - code: Código corto para búsquedas (P1, S0, etc)
     * - name: Nombre legible (Planta 1, Sótano, etc)
     * - description: Descripción del piso
     * - available: Control de disponibilidad
     * - order: Orden visual de los pisos
     * - timestamps: Auditoría de creación/actualización
     */
    public function up(): void
    {
        Schema::create('warehouse_floors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uid')->unique()->comment('Universal ID para URLs/APIs');
            $table->string('code', 50)->unique()->comment('Código único: P1, P2, S0, etc');
            $table->string('name', 100)->comment('Nombre: Planta 1, Sótano, etc');
            $table->text('description')->nullable()->comment('Descripción del piso');
            $table->boolean('available')->default(true)->comment('Disponibilidad del piso');
            $table->integer('order')->default(0)->comment('Orden de visualización');

            // Auditoría
            $table->timestamps();

            // Índices
            $table->index('code');
            $table->index('available');
            $table->index(['available', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_floors');
    }
};
