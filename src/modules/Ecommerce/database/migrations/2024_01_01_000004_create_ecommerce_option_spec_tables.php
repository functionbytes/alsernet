<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_options', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('option_type', 60)->default('text');
            $table->boolean('required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('ecommerce_option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_id');
            $table->string('option_value');
            $table->decimal('affect_price', 15)->default(0)->nullable();
            $table->string('affect_type', 20)->default('fixed')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('ecommerce_option_product', function (Blueprint $table): void {
            $table->foreignId('option_id');
            $table->foreignId('product_id');
            $table->primary(['option_id', 'product_id']);
        });

        Schema::create('ecommerce_global_options', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('option_type', 60)->default('text');
            $table->boolean('required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('ecommerce_global_option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_id');
            $table->string('option_value');
            $table->decimal('affect_price', 15)->default(0)->nullable();
            $table->string('affect_type', 20)->default('fixed')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('ecommerce_specification_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description', 400)->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_specification_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id');
            $table->string('name');
            $table->string('type', 20)->default('text');
            $table->text('options')->nullable();
            $table->string('default_value')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_specification_tables', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description', 400)->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_specification_table_group', function (Blueprint $table): void {
            $table->foreignId('table_id');
            $table->foreignId('group_id');
            $table->tinyInteger('order')->default(0);
            $table->primary(['table_id', 'group_id']);
        });

        Schema::create('ecommerce_product_specification_attribute', function (Blueprint $table): void {
            $table->foreignId('product_id');
            $table->foreignId('attribute_id');
            $table->text('value')->nullable();
            $table->boolean('hidden')->default(false);
            $table->tinyInteger('order')->default(0);
            $table->primary(['product_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_specification_attribute');
        Schema::dropIfExists('ecommerce_specification_table_group');
        Schema::dropIfExists('ecommerce_specification_tables');
        Schema::dropIfExists('ecommerce_specification_attributes');
        Schema::dropIfExists('ecommerce_specification_groups');
        Schema::dropIfExists('ecommerce_global_option_values');
        Schema::dropIfExists('ecommerce_global_options');
        Schema::dropIfExists('ecommerce_option_product');
        Schema::dropIfExists('ecommerce_option_values');
        Schema::dropIfExists('ecommerce_options');
    }
};
