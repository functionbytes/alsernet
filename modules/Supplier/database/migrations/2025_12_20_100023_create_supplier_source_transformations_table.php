<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_source_transformations', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('source_id')->constrained('supplier_sources')->onDelete('cascade');

            $table->string('name', 100);
            $table->text('description')->nullable();

            // Cuándo aplicar
            $table->string('field_name', 100)->nullable(); // NULL = aplicar a todo el registro
            $table->integer('apply_order')->default(0);

            // Tipo de transformación
            $table->string('transformation_type', 50); // 'regex_replace', 'regex_extract', 'mapping', 'formula', 'lookup', 'split', 'join', 'format', 'custom_function'

            // Configuración
            $table->json('transformation_config');

            // Condiciones
            $table->json('apply_condition')->nullable(); // Condición JSON para aplicar la transformación

            $table->boolean('is_enabled')->default(true);

            $table->timestamps();

            // Índices
            $table->index('source_id', 'idx_transformations_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_transformations');
    }
};
