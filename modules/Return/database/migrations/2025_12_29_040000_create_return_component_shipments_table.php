<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_component_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('shipment_number')->unique();
            $table->string('shipment_type')->nullable();
            $table->string('status')->nullable();
            $table->decimal('total_weight', 8, 3)->nullable();
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->json('shipping_address')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->text('shipping_notes')->nullable();
            $table->json('packages_info')->nullable();
            $table->boolean('requires_signature')->default(false);
            $table->boolean('is_fragile')->default(false);
            $table->string('priority')->nullable();
            $table->decimal('insurance_value', 10, 2)->nullable();
            $table->string('shipping_method')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->json('delivery_confirmation')->nullable();
            $table->timestamps();
            $table->index('order_id');
            $table->index('shipment_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_component_shipments');
    }
};
