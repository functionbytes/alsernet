<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('remarketing_customers')->nullOnDelete();
            $table->string('external_id');
            $table->string('order_number', 100)->nullable();
            $table->string('status', 50);
            $table->decimal('total', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->timestamp('placed_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'external_id']);
            $table->index(['customer_id', 'placed_at']);
            $table->index(['store_id', 'placed_at']);
            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_orders');
    }
};
