<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_component_id')->constrained('product_components')->onDelete('cascade');
            $table->foreignId('substitute_component_id')->constrained('product_components')->onDelete('cascade');
            $table->string('substitution_type'); // direct, compatible, upgrade, downgrade
            $table->string('compatibility_level'); // exact, high, medium, low
            $table->decimal('cost_difference', 10, 2)->default(0.00); // Diferencia de costo
            $table->decimal('performance_impact', 5, 2)->default(0.00); // % de impacto en rendimiento
            $table->text('notes')->nullable();
            $table->json('conditions')->nullable(); // Condiciones para la sustitución
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_temporary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Prioridad de sustitución
            $table->timestamps();

            // Índices
            $table->index(['original_component_id', 'is_active']);
            $table->index(['substitute_component_id', 'is_active']);
            $table->index(['substitution_type', 'compatibility_level']);
            $table->index('priority');

            // Evitar duplicados
            $table->unique(['original_component_id', 'substitute_component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_substitutions');
    }
};
