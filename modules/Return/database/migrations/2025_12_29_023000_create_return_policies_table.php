<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('return_days')->default(30);
            $table->json('conditions')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('shop_id')->nullable();
            $table->json('applies_to_categories')->nullable();
            $table->json('applies_to_products')->nullable();
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_return_amount', 10, 2)->nullable();
            $table->boolean('requires_original_packaging')->default(false);
            $table->boolean('allows_opened_products')->default(false);
            $table->string('shipping_cost_coverage')->nullable();
            $table->decimal('restocking_fee_percentage', 5, 2)->nullable();
            $table->timestamps();
            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_policies');
    }
};
