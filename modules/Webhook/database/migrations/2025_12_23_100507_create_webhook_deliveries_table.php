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
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 26)->unique();
            $table->foreignId('integration_id')
                ->constrained('webhook_integrations')
                ->onDelete('cascade');
            $table->foreignId('subscription_id')
                ->constrained('webhook_subscriptions')
                ->onDelete('cascade');
            $table->foreignId('event_id')
                ->constrained('webhook_events')
                ->onDelete('cascade');

            $table->enum('status', ['pending', 'sending', 'success', 'failed', 'dead'])
                ->default('pending');

            $table->integer('attempt_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->text('last_error')->nullable();
            $table->integer('last_http_status')->nullable();
            $table->integer('last_latency_ms')->nullable();

            $table->timestamps();

            $table->index(['status', 'next_retry_at'], 'idx_deliveries_retry');
            $table->index(['integration_id', 'event_id'], 'idx_deliveries_event');
            $table->index('subscription_id', 'idx_deliveries_subscription');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
