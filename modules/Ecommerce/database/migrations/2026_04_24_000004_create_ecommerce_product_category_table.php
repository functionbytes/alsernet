<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_product_category', function (Blueprint $table): void {
            $table->foreignId('product_id')->constrained('ecommerce_products')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('ecommerce_product_categories')->cascadeOnDelete();
            $table->primary(['product_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_category');
    }
};
