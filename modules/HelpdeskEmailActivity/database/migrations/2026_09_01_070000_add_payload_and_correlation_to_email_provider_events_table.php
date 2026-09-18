<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * email_provider_events nació como una simple huella anti-duplicado
 * (provider + provider_event_id, ver create_email_provider_events_table) —
 * sin el payload ni la relación con el email afectado no había forma de
 * depurar por qué un bounce/delivery/open no se reflejaba bien, ni de
 * reintentar la correlación una vez corregido el problema. Esta migración
 * lo convierte en un registro de auditoría completo:
 *
 *  - payload: `{"raw": {...tal como llegó, redactado/acotado por
 *    WebhookPayloadRedactor}, "parsed": {...los campos que
 *    EmailProviderWebhookAdapter::parse() ya extrajo: message_id,
 *    recipient, is_hard, reason, ip, user_agent}}`. "parsed" es lo que
 *    permite reprocesar el evento sin volver a pedírselo al proveedor (ver
 *    WebhookEventsController::reprocess()); "raw" es lo que permite ver
 *    exactamente qué mandó el proveedor cuando el bug está en el propio
 *    parse().
 *  - event_type: 'bounce'|'complaint'|'delivered'|'open' (columna propia,
 *    no solo dentro de payload) — así el listado de Settings → Eventos de
 *    webhook puede filtrar/mostrar el tipo sin tener que leer el JSON.
 *  - email_log_id: a qué EmailLog correlacionó, o null si no correlacionó
 *    con ninguno (mensaje sin Message-ID reconocible, destinatario
 *    ambiguo, etc.) — sin FK en cascada, ver más abajo.
 *  - processed_at: cuándo se ejecutó el correlador sobre este evento (al
 *    recibirlo, o al reprocesarlo manualmente desde
 *    Settings → Eventos de webhook) — null si el tipo de evento nunca
 *    llegó a intentar correlacionar (toggle process_* desactivado).
 *  - duplicate_count: cuántas veces más se reintentó la entrega de este
 *    mismo evento tras la primera vez (los proveedores reintentan el
 *    webhook si no reciben 2xx a tiempo). Antes de esta columna, un
 *    reintento duplicado no dejaba ningún rastro (markSeenIfNew() solo
 *    evitaba el insert) — sin esto, el panel de salud no podría mostrar
 *    "duplicados descartados" por proveedor, que es precisamente la
 *    métrica pedida para detectar un proveedor reintentando en bucle.
 *
 * DECISIÓN (email_log_id SIN cascadeOnDelete): email_logs acaba de ganar
 * SoftDeletes (ver add_deleted_at_to_email_logs_table) pero el borrado
 * GDPR (EmailLogComplianceHandler) y la purga por retención
 * (PruneEmailLogsCommand) siguen usando forceDelete(), un DELETE real.
 * Un FK con cascadeOnDelete() borraría la fila de auditoría del webhook
 * exactamente cuando más falta hace conservarla (para demostrar qué
 * evento de proveedor se procesó, aunque el email ya no exista). Se usa
 * nullOnDelete() en su lugar: el evento sobrevive siempre, solo se
 * desvincula del email ya desaparecido.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_provider_events')) {
            return;
        }

        Schema::table('email_provider_events', function (Blueprint $table) {
            if (! Schema::hasColumn('email_provider_events', 'payload')) {
                $table->json('payload')->nullable()->after('provider_event_id');
            }

            if (! Schema::hasColumn('email_provider_events', 'event_type')) {
                $table->string('event_type', 20)->nullable()->after('payload');
            }

            if (! Schema::hasColumn('email_provider_events', 'duplicate_count')) {
                $table->unsignedInteger('duplicate_count')->default(0)->after('payload');
            }

            if (! Schema::hasColumn('email_provider_events', 'email_log_id')) {
                $table->foreignId('email_log_id')->nullable()->after('duplicate_count')
                    ->constrained('email_logs')->nullOnDelete();
            }

            if (! Schema::hasColumn('email_provider_events', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('email_log_id');
            }
        });

        // Listado/health panel de Settings → Eventos de webhook: filtra por
        // fecha (volumen del día) y por proveedor+fecha (salud por
        // proveedor) — el UNIQUE(provider, provider_event_id) ya existente
        // no cubre bien un rango por created_at.
        Schema::table('email_provider_events', function (Blueprint $table) {
            $table->index('created_at', 'email_provider_events_created_at_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_provider_events')) {
            return;
        }

        Schema::table('email_provider_events', function (Blueprint $table) {
            if (Schema::hasColumn('email_provider_events', 'email_log_id')) {
                $table->dropConstrainedForeignId('email_log_id');
            }

            $table->dropIndex('email_provider_events_created_at_index');
            $table->dropColumn(['payload', 'event_type', 'duplicate_count', 'processed_at']);
        });
    }
};
