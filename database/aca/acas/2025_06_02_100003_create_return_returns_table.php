<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('component_id')->constrained('product_components')->onDelete('cascade');
            $table->string('return_number')->unique();
            $table->string('return_type'); // defective, damaged, wrong_item, customer_request, upgrade
            $table->string('status')->default('requested'); // requested, approved, rejected, in_transit, received, processed
            $table->integer('quantity_returned');
            $table->string('condition'); // working, defective, damaged, missing_parts
            $table->text('return_reason');
            $table->text('customer_description')->nullable();
            $table->decimal('return_value', 10, 2)->default(0.00);
            $table->decimal('restocking_fee', 10, 2)->default(0.00);
            $table->decimal('refund_amount', 10, 2)->default(0.00);
            $table->json('serial_numbers')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('return_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('processed_date')->nullable();
            $table->string('return_tracking_number')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->json('quality_assessment')->nullable();
            $table->string('disposition'); // restock, repair, scrap, vendor_return
            $table->boolean('is_warranty_claim')->default(false);
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('photos')->nullable(); // URLs de fotos del retorno
            $table->timestamps();

            // Índices
            $table->index(['order_id', 'status']);
            $table->index(['component_id', 'condition']);
            $table->index(['return_type', 'status']);
            $table->index('return_number');
            $table->index(['return_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_returns');
    }
};
