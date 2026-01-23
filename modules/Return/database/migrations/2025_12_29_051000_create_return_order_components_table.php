<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_order_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable();
            $table->foreignId('component_id')->constrained('product_components')->cascadeOnDelete();
            $table->integer('quantity_required')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_allocated')->default(0);
            $table->integer('quantity_shipped')->default(0);
            $table->integer('quantity_missing')->default(0);
            $table->string('status')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->decimal('deduction_amount', 10, 2)->nullable();
            $table->string('deduction_type')->nullable();
            $table->decimal('deduction_applied', 10, 2)->nullable();
            $table->boolean('is_essential')->default(false);
            $table->boolean('can_substitute')->default(false);
            $table->foreignId('substitute_component_id')->nullable();
            $table->integer('substitute_quantity')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expected_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('shipment_tracking')->nullable();
            $table->dateTime('reserved_at')->nullable();
            $table->dateTime('allocated_at')->nullable();
            $table->dateTime('shipped_at')->nullable();
            $table->timestamps();
            $table->index('order_id');
            $table->index('component_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_order_components');
    }
};
