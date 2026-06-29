<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('ecommerce_customers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('ecommerce_products')->cascadeOnDelete();
            $table->integer('qty')->default(1);
            $table->string('interval', 20);
            $table->decimal('price', 10, 2);
            $table->string('status', 20)->default('active');
            $table->timestamp('next_billing_at');
            $table->timestamp('last_billed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index('next_billing_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_subscriptions');
    }
};
