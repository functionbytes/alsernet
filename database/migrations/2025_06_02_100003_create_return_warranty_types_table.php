<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Garantía del fabricante, garantía extendida, etc.
            $table->string('code')->unique(); // MANUFACTURER, EXTENDED, STORE, etc.
            $table->text('description')->nullable();
            $table->integer('default_duration_months')->default(12);
            $table->integer('max_duration_months')->nullable();
            $table->json('coverage_details'); // Qué cubre la garantía
            $table->json('exclusions')->nullable(); // Qué NO cubre
            $table->decimal('cost_percentage', 5, 2)->default(0.00); // % del precio del producto
            $table->decimal('fixed_cost', 10, 2)->default(0.00); // Costo fijo
            $table->boolean('transferable')->default(false); // Se puede transferir
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['code', 'is_active']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_types');
    }
};
