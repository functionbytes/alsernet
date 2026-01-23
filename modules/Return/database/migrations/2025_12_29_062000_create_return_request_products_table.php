<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_request_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained('return_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable();
            $table->string('product_code')->nullable();
            $table->string('product_name')->nullable();
            $table->decimal('quantity', 8, 2)->default(0);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->foreignId('reason_id')->nullable();
            $table->string('return_condition')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_approved')->nullable();
            $table->decimal('approved_quantity', 8, 2)->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->boolean('replacement_requested')->default(false);
            $table->timestamps();
            $table->index('return_request_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_request_products');
    }
};
