<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 60)->default('published');
            $table->string('slug')->unique();
            $table->text('images')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->integer('order')->unsigned()->default(0);
            $table->integer('quantity')->unsigned()->nullable();
            $table->boolean('allow_checkout_when_out_of_stock')->default(false);
            $table->boolean('with_storehouse_management')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->foreignId('brand_id')->nullable()->constrained('ecommerce_brands')->nullOnDelete();
            $table->boolean('is_variation')->default(false);
            $table->string('sale_type', 60)->default('default');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->decimal('length', 15, 2)->nullable();
            $table->decimal('wide', 15, 2)->nullable();
            $table->decimal('height', 15, 2)->nullable();
            $table->decimal('weight', 15, 2)->nullable();
            $table->integer('views')->unsigned()->default(0);
            $table->string('stock_status', 60)->default('in_stock');
            $table->string('barcode')->nullable();
            $table->decimal('cost_per_item', 15, 2)->nullable();
            $table->integer('minimum_order_quantity')->unsigned()->default(1);
            $table->integer('maximum_order_quantity')->unsigned()->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('sku');
            $table->index('stock_status');
            $table->index('is_featured');
            $table->index('brand_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_products');
    }
};
