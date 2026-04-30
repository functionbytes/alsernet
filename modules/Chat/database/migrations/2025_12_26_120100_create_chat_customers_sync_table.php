<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_customers_sync', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->integer('prestashop_customer_id')->index();
            $table->foreignId('customer_id')->constrained('chat_customers')->cascadeOnDelete();
            $table->string('email');
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->json('additional_attributes')->nullable();
            $table->timestamp('last_sync_at');
            $table->timestamps();

            $table->unique(['account_id', 'prestashop_customer_id'], 'ps_sync_account_ps_customer_unique');
            $table->index('customer_id', 'ps_sync_hd_customer_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_customers_sync');
    }
};
