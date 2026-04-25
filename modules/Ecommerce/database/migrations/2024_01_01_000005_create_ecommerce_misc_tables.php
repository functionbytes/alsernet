<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_flash_sales', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('status', 60)->default('published');
            $table->timestamps();
        });

        Schema::create('ecommerce_flash_sale_products', function (Blueprint $table): void {
            $table->foreignId('flash_sale_id');
            $table->foreignId('product_id');
            $table->decimal('price', 15)->default(0);
            $table->integer('quantity')->default(0);
            $table->integer('sold')->default(0);
            $table->primary(['flash_sale_id', 'product_id']);
        });

        Schema::create('ecommerce_product_labels', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->string('text_color', 20)->nullable();
            $table->string('status', 60)->default('published');
            $table->timestamps();
        });

        Schema::create('ecommerce_product_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('file');
            $table->integer('file_size')->default(0);
            $table->string('file_ext')->nullable();
            $table->boolean('is_free')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ecommerce_product_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id');
            $table->bigInteger('views')->default(0);
            $table->date('date')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_customer_recently_viewed_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('product_id');
            $table->string('session_id')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_review_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_id');
            $table->foreignId('user_id')->nullable();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('ecommerce_shared_wishlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id');
            $table->string('token')->unique();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_customer_deletion_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id');
            $table->string('token')->nullable();
            $table->string('status', 60)->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_customer_used_coupons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id');
            $table->foreignId('discount_id');
            $table->foreignId('order_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_customer_used_coupons');
        Schema::dropIfExists('ecommerce_customer_deletion_requests');
        Schema::dropIfExists('ecommerce_shared_wishlists');
        Schema::dropIfExists('ecommerce_review_replies');
        Schema::dropIfExists('ecommerce_customer_recently_viewed_products');
        Schema::dropIfExists('ecommerce_product_views');
        Schema::dropIfExists('ecommerce_product_files');
        Schema::dropIfExists('ecommerce_product_labels');
        Schema::dropIfExists('ecommerce_flash_sale_products');
        Schema::dropIfExists('ecommerce_flash_sales');
    }
};
