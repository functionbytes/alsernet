<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('symbol', 10);
            $table->tinyInteger('is_prefix_symbol')->unsigned()->default(0);
            $table->tinyInteger('decimals')->unsigned()->default(0)->nullable();
            $table->integer('order')->default(0)->unsigned()->nullable();
            $table->tinyInteger('is_default')->default(0);
            $table->double('exchange_rate')->default(1);
            $table->timestamps();
        });

        Schema::create('ecommerce_product_collections', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('description', 400)->nullable();
            $table->string('image')->nullable();
            $table->string('status', 60)->default('published');
            $table->timestamps();
        });

        Schema::create('ecommerce_product_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('description', 400)->nullable();
            $table->string('status', 60)->default('published');
            $table->timestamps();
        });

        Schema::create('ecommerce_product_tag_product', function (Blueprint $table): void {
            $table->foreignId('product_id')->index();
            $table->foreignId('tag_id')->index();
            $table->primary(['product_id', 'tag_id']);
        });

        Schema::create('ecommerce_product_collection_products', function (Blueprint $table): void {
            $table->foreignId('product_collection_id');
            $table->foreignId('product_id');
            $table->primary(['product_id', 'product_collection_id']);
            $table->index('product_collection_id', 'ec_coll_prod_coll_id_idx');
            $table->index('product_id', 'ec_coll_prod_prod_id_idx');
        });

        Schema::create('ecommerce_product_attribute_sets', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 120)->nullable();
            $table->string('display_layout')->default('swatch_dropdown');
            $table->tinyInteger('is_searchable')->unsigned()->default(1);
            $table->tinyInteger('is_comparable')->unsigned()->default(1);
            $table->tinyInteger('is_use_in_product_listing')->unsigned()->default(0);
            $table->string('status', 60)->default('published');
            $table->tinyInteger('order')->unsigned()->default(0);
            $table->timestamps();
        });

        Schema::create('ecommerce_product_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_set_id');
            $table->string('title', 120);
            $table->string('slug', 120)->nullable();
            $table->string('color', 120)->nullable();
            $table->string('image')->nullable();
            $table->tinyInteger('is_default')->unsigned()->default(0);
            $table->tinyInteger('order')->unsigned()->default(0);
            $table->string('status', 60)->default('published');
            $table->timestamps();
        });

        Schema::create('ecommerce_product_with_attribute_set', function (Blueprint $table): void {
            $table->foreignId('attribute_set_id');
            $table->foreignId('product_id');
            $table->tinyInteger('order')->unsigned()->default(0);
            $table->primary(['product_id', 'attribute_set_id']);
        });

        Schema::create('ecommerce_product_variations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('configurable_product_id');
            $table->tinyInteger('is_default')->default(0);
        });

        Schema::create('ecommerce_product_variation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_id');
            $table->foreignId('variation_id');
            $table->unique(['attribute_id', 'variation_id'], 'ec_var_items_attr_var_unique');
        });

        Schema::create('ecommerce_taxes', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->float('percentage', 8, 6)->nullable();
            $table->integer('priority')->nullable();
            $table->string('status', 60)->default('published');
            $table->timestamps();
        });

        Schema::create('ecommerce_shipping', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('country', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_wish_lists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id');
            $table->foreignId('product_id');
            $table->timestamps();
        });

        Schema::create('ecommerce_grouped_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_product_id');
            $table->foreignId('product_id');
            $table->integer('fixed_qty')->default(1);
        });

        Schema::create('ecommerce_customer_password_resets', function (Blueprint $table): void {
            $table->string('email')->index();
            $table->string('token')->index();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('ecommerce_product_related_relations', function (Blueprint $table): void {
            $table->foreignId('from_product_id')->index();
            $table->foreignId('to_product_id')->index();
            $table->primary(['from_product_id', 'to_product_id']);
        });

        Schema::create('ecommerce_product_cross_sale_relations', function (Blueprint $table): void {
            $table->foreignId('from_product_id')->index();
            $table->foreignId('to_product_id')->index();
            $table->primary(['from_product_id', 'to_product_id']);
        });

        Schema::create('ecommerce_product_up_sale_relations', function (Blueprint $table): void {
            $table->foreignId('from_product_id')->index();
            $table->foreignId('to_product_id')->index();
            $table->primary(['from_product_id', 'to_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_up_sale_relations');
        Schema::dropIfExists('ecommerce_product_cross_sale_relations');
        Schema::dropIfExists('ecommerce_product_related_relations');
        Schema::dropIfExists('ecommerce_customer_password_resets');
        Schema::dropIfExists('ecommerce_grouped_products');
        Schema::dropIfExists('ecommerce_wish_lists');
        Schema::dropIfExists('ecommerce_shipping');
        Schema::dropIfExists('ecommerce_taxes');
        Schema::dropIfExists('ecommerce_product_variation_items');
        Schema::dropIfExists('ecommerce_product_variations');
        Schema::dropIfExists('ecommerce_product_with_attribute_set');
        Schema::dropIfExists('ecommerce_product_attributes');
        Schema::dropIfExists('ecommerce_product_attribute_sets');
        Schema::dropIfExists('ecommerce_product_collection_products');
        Schema::dropIfExists('ecommerce_product_tag_product');
        Schema::dropIfExists('ecommerce_product_tags');
        Schema::dropIfExists('ecommerce_product_collections');
        Schema::dropIfExists('ecommerce_currencies');
    }
};
