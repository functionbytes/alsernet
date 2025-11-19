<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de ESTILOS DE ESTANTERÍAS
     *
     * Define qué tipo de estantería es (pasillo, isla, pared) y sus características.
     * Las caras y niveles se pueden configurar por estilo.
     *
     * Estructura:
     * - id: Identificador único
     * - uid: UUID universal para URLs y APIs
     * - code: Código único (ROW, ISLAND, WALL)
     * - name: Nombre legible
     * - description: Descripción del estilo
     * - faces: JSON array de caras disponibles ["left", "right", "front", "back"]
     * - default_levels: Niveles por defecto (profundidad vertical)
     * - default_sections: Secciones por defecto (divisiones horizontales)
     * - available: Disponibilidad del estilo
     * - timestamps: Auditoría
     */
    public function up(): void
    {
        Schema::create('warehouse_stand_styles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uid')->unique()->comment('Universal ID para URLs/APIs');
            $table->string('code', 50)->unique()->comment('Código único: ROW, ISLAND, WALL');
            $table->string('name', 100)->comment('Nombre: Pasillo Lineal, Isla, etc');
            $table->text('description')->nullable()->comment('Descripción del tipo');

            // Configuración de caras (left, right, front, back)
            // Ejemplo: {"faces": ["left","right"], "default_levels": 3, "default_sections": 5}
            $table->json('faces')->default('[]')->comment('Caras disponibles: left, right, front, back');
            $table->integer('default_levels')->default(3)->comment('Niveles por defecto');
            $table->integer('default_sections')->default(5)->comment('Secciones por defecto');

            // Estado
            $table->boolean('available')->default(true)->comment('Disponibilidad del estilo');

            // Auditoría
            $table->timestamps();

            // Índices
            $table->index('code');
            $table->index('available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stand_styles');
    }
};
