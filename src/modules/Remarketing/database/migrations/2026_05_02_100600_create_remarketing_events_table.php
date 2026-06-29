<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('remarketing_stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('remarketing_customers')->nullOnDelete();
            $table->string('visitor_id', 64)->nullable();
            $table->string('type', 60);
            $table->json('properties')->nullable();
            $table->enum('source', ['pixel', 'webhook', 'api', 'manual'])->default('webhook');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('received_at')->useCurrent();

            $table->index(['store_id', 'customer_id', 'occurred_at']);
            $table->index(['store_id', 'type', 'occurred_at']);
            $table->index(['visitor_id', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_events');
    }
};
