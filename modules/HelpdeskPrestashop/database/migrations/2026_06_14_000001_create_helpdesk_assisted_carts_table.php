<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_assisted_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->string('status', 20)->default('building');
            $table->string('discount_code')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->unsignedBigInteger('ecommerce_order_id')->nullable();
            $table->string('order_code')->nullable();
            $table->string('payment_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('helpdesk_customers')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('helpdesk_conversations')->onDelete('set null');

            $table->index('customer_id');
            $table->index('conversation_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_assisted_carts');
    }
};
