<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('remarketing_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('remarketing_products')->nullOnDelete();
            $table->string('external_product_id')->nullable();
            $table->string('title', 500);
            $table->string('sku', 100)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->text('image_url')->nullable();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_order_items');
    }
};
