<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecommerce_payments')) {
            return;
        }

        Schema::create('ecommerce_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('charge_id')->unique();
            $table->foreignId('order_id')->nullable()->constrained('ecommerce_orders')->nullOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('payment_fee', 15, 2)->nullable();
            $table->string('currency', 10)->default('COP');
            $table->string('payment_channel', 60)->default('wompi');
            $table->text('description')->nullable();
            $table->string('status', 60)->default('pending');
            $table->string('payment_type', 60)->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_type')->nullable();
            $table->decimal('refunded_amount', 15, 2)->nullable();
            $table->text('refund_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('charge_id');
            $table->index('status');
            $table->index('payment_channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_payments');
    }
};
