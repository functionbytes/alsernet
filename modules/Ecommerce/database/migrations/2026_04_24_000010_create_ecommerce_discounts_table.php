<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_discounts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('code')->nullable()->unique();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('quantity')->unsigned()->nullable();
            $table->integer('total_used')->unsigned()->default(0);
            $table->decimal('value', 15, 2)->default(0);
            $table->string('type', 60)->default('fixed'); // fixed | percentage | free_shipping
            $table->string('target', 60)->default('all_products');
            $table->decimal('min_order_price', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_discounts');
    }
};
