<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotencia de eventos de proveedor (SES/Mailgun/Postmark/Mailrelay
 * webhooks) — cada adapter rellena ParsedEmailEvent::providerEventId con el
 * identificador único que el proveedor asigna a la notificación (p.ej.
 * mail.messageId de SES). Sin esto, un reintento de entrega del proveedor
 * (todos reintentan si no reciben 2xx a tiempo) volvía a correlacionar el
 * mismo bounce/complaint una segunda vez.
 *
 * UNIQUE(provider, provider_event_id) hace el registro atómico: dos
 * peticiones concurrentes para el mismo evento chocan en el índice y solo
 * una gana (ver ProviderWebhookEvent::markSeenIfNew()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_provider_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('provider_event_id', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['provider', 'provider_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_provider_events');
    }
};
