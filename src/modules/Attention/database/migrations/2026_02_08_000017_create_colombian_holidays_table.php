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
        Schema::create('colombian_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique()->comment('Fecha del festivo');
            $table->string('name')->comment('Nombre del festivo');
            $table->enum('type', ['fixed', 'movable'])->default('fixed')->comment('Tipo de festivo: fijo o móvil');
            $table->boolean('is_monday_law')->default(false)->comment('Se traslada al lunes siguiente según Ley Emiliani');
            $table->timestamps();

            // Índices
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colombian_holidays');
    }
};
