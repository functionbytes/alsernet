<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('warehouse_stands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uid')->unique()->comment('Universal ID para URLs/APIs');

            // Relaciones
            $table->unsignedBigInteger('floor_id')->comment('ID del piso');
            $table->unsignedBigInteger('stand_style_id')->comment('ID del estilo');

            // Identificación
            $table->string('code', 50)->unique()->comment('Código único: PASILLO13A, ISLA02, etc');
            $table->string('barcode', 100)->nullable()->unique()->comment('Código de barras físico');

            // Posicionamiento
            $table->integer('position_x')->default(0)->comment('Coordenada X (metros)');
            $table->integer('position_y')->default(0)->comment('Coordenada Y (metros)');
            $table->integer('position_z')->default(0)->nullable()->comment('Altura/Nivel para visualización 3D');

            // Configuración
            $table->integer('total_levels')->comment('Niveles totales de la estantería');
            $table->integer('total_sections')->comment('Secciones totales');
            $table->decimal('capacity', 10, 2)->nullable()->comment('Peso máximo permitido (kg)');

            // Estado
            $table->boolean('available')->default(true)->comment('Estado de la estantería');
            $table->text('notes')->nullable()->comment('Notas: mantenimiento, daños, etc');

            // Auditoría
            $table->timestamps();

            // Foreign keys
            $table->foreign('floor_id')
                ->references('id')
                ->on('warehouse_floors')
                ->onDelete('cascade');

            $table->foreign('stand_style_id')
                ->references('id')
                ->on('warehouse_stand_styles')
                ->onDelete('restrict');

            // Índices
            $table->index('code');
            $table->index('barcode');
            $table->index('floor_id');
            $table->index('stand_style_id');
            $table->index('available');
            $table->index(['floor_id', 'available']);
            $table->index(['position_x', 'position_y']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stands');
    }
};
