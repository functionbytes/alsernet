<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('name'); // Nombre del componente
            $table->string('code')->unique(); // Código único del componente
            $table->string('sku')->unique(); // SKU del componente
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // electronics, mechanical, accessory, etc.
            $table->string('type'); // essential, optional, accessory, consumable
            $table->integer('quantity_per_product')->default(1); // Cantidad necesaria por producto
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->decimal('replacement_cost', 10, 2)->default(0.00);
            $table->decimal('weight', 8, 3)->default(0.000); // Peso en kg
            $table->json('dimensions')->nullable(); // largo, ancho, alto
            $table->string('supplier_sku')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->integer('lead_time_days')->default(7); // Tiempo de suministro
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->nullable();
            $table->integer('reorder_point')->default(0);
            $table->integer('current_stock')->default(0);
            $table->integer('reserved_stock')->default(0); // Stock reservado para órdenes
            $table->integer('available_stock')->storedAs('current_stock - reserved_stock');
            $table->boolean('is_trackable')->default(true); // Si se trackea individualmente
            $table->boolean('has_serial_numbers')->default(false);
            $table->boolean('is_replaceable')->default(true); // Si se puede reemplazar independientemente
            $table->boolean('affects_functionality')->default(true); // Si afecta la funcionalidad del producto
            $table->decimal('deduction_percentage', 5, 2)->default(0.00); // % de deducción si falta
            $table->decimal('fixed_deduction_amount', 10, 2)->default(0.00); // Monto fijo de deducción
            $table->string('compatibility_level')->default('strict'); // strict, compatible, universal
            $table->json('compatible_alternatives')->nullable(); // IDs de componentes alternativos
            $table->json('metadata')->nullable(); // Información adicional
            $table->string('location')->nullable(); // Ubicación en almacén
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices
            $table->index(['product_id', 'type']);
            $table->index(['category', 'is_active']);
            $table->index(['current_stock', 'minimum_stock']);
            $table->index(['supplier_id', 'is_active']);
            $table->index('reorder_point');
            $table->index(['code', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_product_components');
    }
};
