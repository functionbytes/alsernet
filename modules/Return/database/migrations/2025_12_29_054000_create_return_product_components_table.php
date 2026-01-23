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
            $table->foreignId('product_id')->constrained('inventaries')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->decimal('quantity_per_product', 8, 2)->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('replacement_cost', 10, 2)->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->string('supplier_sku')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->cascadeOnDelete();
            $table->integer('lead_time_days')->nullable();
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->nullable();
            $table->integer('reorder_point')->default(0);
            $table->integer('current_stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->boolean('is_trackable')->default(false);
            $table->boolean('has_serial_numbers')->default(false);
            $table->boolean('is_replaceable')->default(false);
            $table->boolean('affects_functionality')->default(false);
            $table->decimal('deduction_percentage', 5, 2)->nullable();
            $table->decimal('fixed_deduction_amount', 10, 2)->nullable();
            $table->string('compatibility_level')->nullable();
            $table->json('compatible_alternatives')->nullable();
            $table->json('metadata')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('product_id');
            $table->index('supplier_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_product_components');
    }
};
