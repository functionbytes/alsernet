<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_product_return_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->string('rule_type')->default('category'); // category, product, global
            $table->boolean('is_returnable')->default(true);
            $table->integer('return_period_days')->nullable(); // null = sin límite
            $table->decimal('max_return_percentage', 5, 2)->default(100.00); // % del precio original
            $table->json('conditions')->nullable(); // Condiciones específicas
            $table->json('excluded_reasons')->nullable(); // Razones de devolución excluidas
            $table->boolean('requires_original_packaging')->default(false);
            $table->boolean('requires_receipt')->default(true);
            $table->boolean('allow_partial_return')->default(true);
            $table->text('special_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Mayor número = mayor prioridad
            $table->timestamps();

            // Índices
            $table->index(['category_id', 'is_active']);
            $table->index(['product_id', 'is_active']);
            $table->index(['rule_type', 'is_active']);
            $table->index('priority');

            // Constraint: Solo puede tener category_id O product_id, no ambos
            $table->check('(category_id IS NOT NULL AND product_id IS NULL) OR (category_id IS NULL AND product_id IS NOT NULL) OR (category_id IS NULL AND product_id IS NULL)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_product_return_rules');
    }
};
