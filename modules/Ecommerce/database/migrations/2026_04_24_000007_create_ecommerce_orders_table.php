<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('ecommerce_customers')->nullOnDelete();
            $table->string('status', 60)->default('pending');
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->string('discount_description')->nullable();
            $table->string('shipping_method')->nullable();
            $table->string('shipping_option')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status', 60)->default('pending');
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('customer_id');
            $table->index('payment_status');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_orders');
    }
};
