<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->foreignId('buyer_customer_id')->nullable()->constrained('ecommerce_customers')->nullOnDelete();
            $table->string('recipient_email', 255)->nullable();
            $table->string('recipient_name', 255)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('active'); // active, used, expired, cancelled
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('ecommerce_orders');
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_gift_cards');
    }
};
