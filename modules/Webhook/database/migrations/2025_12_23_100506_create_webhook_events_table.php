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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 26)->unique();
            $table->foreignId('integration_id')
                ->constrained('webhook_integrations')
                ->onDelete('cascade');

            $table->string('event_key', 100)->comment('order.created');
            $table->string('event_version', 10)->default('v1');
            $table->string('external_event_id', 255)->nullable()->comment('ID del evento original');
            $table->string('idempotency_key', 255)->unique()->comment('Para evitar duplicados');

            $table->json('payload')->comment('Payload completo recibido');
            $table->string('payload_hash', 64)->comment('SHA256 del payload');

            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['integration_id', 'event_key'], 'idx_events_integration_key');
            $table->index('idempotency_key', 'idx_events_idempotency');
            $table->index('received_at', 'idx_events_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
