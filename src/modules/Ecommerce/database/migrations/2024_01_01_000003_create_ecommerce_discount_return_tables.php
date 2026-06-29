<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_discount_products', function (Blueprint $table): void {
            $table->foreignId('discount_id');
            $table->foreignId('product_id');
            $table->primary(['discount_id', 'product_id']);
        });

        Schema::create('ecommerce_discount_customers', function (Blueprint $table): void {
            $table->foreignId('discount_id');
            $table->foreignId('customer_id');
            $table->primary(['discount_id', 'customer_id']);
        });

        Schema::create('ecommerce_discount_product_collections', function (Blueprint $table): void {
            $table->foreignId('discount_id');
            $table->foreignId('product_collection_id');
            $table->primary(['discount_id', 'product_collection_id']);
        });

        Schema::create('ecommerce_order_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('store_id')->nullable();
            $table->foreignId('user_id');
            $table->text('reason')->nullable();
            $table->string('order_status')->nullable();
            $table->string('return_status');
            $table->timestamps();
        });

        Schema::create('ecommerce_order_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_return_id');
            $table->foreignId('order_product_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('qty')->default(1);
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_order_return_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_return_id');
            $table->string('action', 120);
            $table->string('description', 400)->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_order_referrals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->string('ip')->nullable();
            $table->string('landing_domain')->nullable();
            $table->string('landing_page')->nullable();
            $table->text('landing_params')->nullable();
            $table->string('referral_domain')->nullable();
            $table->text('referral_url')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_order_tax_information', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_tax_code')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_tax_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tax_id');
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->float('percentage', 8, 6)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_tax_rules');
        Schema::dropIfExists('ecommerce_order_tax_information');
        Schema::dropIfExists('ecommerce_order_referrals');
        Schema::dropIfExists('ecommerce_order_return_histories');
        Schema::dropIfExists('ecommerce_order_return_items');
        Schema::dropIfExists('ecommerce_order_returns');
        Schema::dropIfExists('ecommerce_discount_product_collections');
        Schema::dropIfExists('ecommerce_discount_customers');
        Schema::dropIfExists('ecommerce_discount_products');
    }
};
