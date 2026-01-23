<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('customer_id')->nullable();
            $table->integer('shop_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->foreignId('status_id')->nullable();
            $table->foreignId('type_id')->nullable();
            $table->foreignId('reason_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('return_address')->nullable();
            $table->string('pickup_selection')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('received_date')->nullable();
            $table->dateTime('pickup_date')->nullable();
            $table->boolean('is_refunded')->default(false);
            $table->boolean('is_wallet_used')->default(false);
            $table->string('iban')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('logistics_mode')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->dateTime('request_at')->nullable();
            $table->timestamps();
            $table->index('order_id');
            $table->index('customer_id');
            $table->index('status_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
