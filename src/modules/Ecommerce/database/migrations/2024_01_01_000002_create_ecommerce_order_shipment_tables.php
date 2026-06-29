<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_order_addresses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('address')->nullable();
            $table->foreignId('order_id');
            $table->string('type', 20)->default('shipping');
        });

        Schema::create('ecommerce_shipping_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('shipping_id');
            $table->string('type', 60)->default('based_on_price')->nullable();
            $table->foreignId('currency_id')->nullable();
            $table->decimal('from', 15)->default(0)->nullable();
            $table->decimal('to', 15)->default(0)->nullable();
            $table->decimal('price', 15)->default(0)->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_shipping_rule_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_rule_id');
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->decimal('adjustment_price', 15)->default(0)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('ecommerce_shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('user_id')->nullable();
            $table->float('weight')->default(0)->nullable();
            $table->string('shipment_id', 120)->nullable();
            $table->string('rate_id', 120)->nullable();
            $table->string('note', 120)->nullable();
            $table->string('status', 120)->default('pending');
            $table->decimal('cod_amount', 15)->default(0)->nullable();
            $table->string('cod_status', 60)->default('pending');
            $table->string('cross_checking_status', 60)->default('pending');
            $table->decimal('price', 15)->default(0)->nullable();
            $table->foreignId('store_id')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_store_locators', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60);
            $table->string('email', 60)->nullable();
            $table->string('phone', 20);
            $table->string('address');
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->boolean('is_primary')->default(false)->nullable();
            $table->boolean('is_shipping_location')->default(true)->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_shipment_histories', function (Blueprint $table): void {
            $table->id();
            $table->string('action', 120);
            $table->string('description', 400)->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('shipment_id');
            $table->foreignId('order_id');
            $table->timestamps();
        });

        Schema::create('ecommerce_invoices', function (Blueprint $table): void {
            $table->id();
            $table->morphs('reference');
            $table->string('code')->unique();
            $table->string('customer_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_logo')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_tax_id')->nullable();
            $table->decimal('sub_total', 15)->unsigned();
            $table->decimal('tax_amount', 15)->default(0)->unsigned();
            $table->decimal('shipping_amount', 15)->default(0)->unsigned();
            $table->decimal('discount_amount', 15)->default(0)->unsigned();
            $table->string('shipping_option', 60)->nullable();
            $table->string('shipping_method', 60)->default('default');
            $table->string('coupon_code', 120)->nullable();
            $table->string('discount_description')->nullable();
            $table->decimal('amount', 15)->unsigned();
            $table->text('description')->nullable();
            $table->foreignId('payment_id')->nullable()->index();
            $table->string('status')->index()->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id');
            $table->morphs('reference');
            $table->string('name');
            $table->string('description', 400)->nullable();
            $table->string('image')->nullable();
            $table->integer('qty')->unsigned();
            $table->decimal('sub_total', 15)->unsigned();
            $table->decimal('tax_amount', 15)->default(0)->unsigned();
            $table->decimal('discount_amount', 15)->default(0)->unsigned();
            $table->decimal('amount', 15)->unsigned();
            $table->text('options')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_invoice_items');
        Schema::dropIfExists('ecommerce_invoices');
        Schema::dropIfExists('ecommerce_shipment_histories');
        Schema::dropIfExists('ecommerce_store_locators');
        Schema::dropIfExists('ecommerce_shipments');
        Schema::dropIfExists('ecommerce_shipping_rule_items');
        Schema::dropIfExists('ecommerce_shipping_rules');
        Schema::dropIfExists('ecommerce_order_addresses');
    }
};
