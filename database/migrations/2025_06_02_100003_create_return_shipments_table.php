<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('shipment_number')->unique();
            $table->string('shipment_type')->default('partial'); // partial, complete, backorder
            $table->string('status')->default('preparing'); // preparing, shipped, in_transit, delivered, returned
            $table->decimal('total_weight', 8, 3)->default(0.000);
            $table->decimal('shipping_cost', 10, 2)->default(0.00);
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->json('shipping_address');
            $table->date('shipped_date')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->text('shipping_notes')->nullable();
            $table->json('packages_info')->nullable(); // Información de paquetes
            $table->boolean('requires_signature')->default(false);
            $table->boolean('is_fragile')->default(false);
            $table->string('priority')->default('normal'); // normal, high, urgent
            $table->decimal('insurance_value', 10, 2)->default(0.00);
            $table->string('shipping_method')->nullable(); // standard, express, overnight
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('delivery_confirmation')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['order_id', 'status']);
            $table->index(['shipment_type', 'status']);
            $table->index('tracking_number');
            $table->index(['shipped_date', 'status']);
            $table->index('carrier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_shipments');
    }
};
