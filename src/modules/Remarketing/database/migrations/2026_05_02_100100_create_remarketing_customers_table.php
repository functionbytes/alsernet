<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->string('email');
            $table->char('email_hash', 64);
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('external_id')->nullable();
            $table->json('tags')->nullable();
            $table->json('attributes')->nullable();
            $table->tinyInteger('consent_marketing')->default(0);
            $table->timestamp('consent_confirmed_at')->nullable();
            $table->string('double_optin_token', 128)->nullable();
            $table->timestamp('double_optin_sent_at')->nullable();
            $table->enum('status', ['subscribed', 'unsubscribed', 'bounced', 'complained', 'pending'])->default('pending');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->unsignedTinyInteger('rfm_recency')->nullable();
            $table->unsignedTinyInteger('rfm_frequency')->nullable();
            $table->unsignedTinyInteger('rfm_monetary')->nullable();
            $table->decimal('clv_historical', 12, 2)->nullable();
            $table->unsignedInteger('orders_count')->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->date('birthday')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['store_id', 'email']);
            $table->index('email_hash');
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'last_order_at']);
            $table->index(['rfm_recency', 'rfm_frequency', 'rfm_monetary'], 'rmk_customers_rfm_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_customers');
    }
};
