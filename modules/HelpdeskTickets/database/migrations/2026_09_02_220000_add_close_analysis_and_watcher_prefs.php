<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos del modal de cierre y de las preferencias de seguidor.
 *
 * El cierre solo guardaba `close_reason` (100 caracteres): no había dónde
 * poner por QUÉ pasó (causa raíz, que es lo que se agrupa en los informes)
 * ni el resumen que redacta el agente. Los seguidores, por su parte, recibían
 * todos los avisos del ticket sin poder elegir cuáles.
 */
return new class extends Migration
{
    public function getConnection(): ?string
    {
        return 'helpdesk';
    }

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_tickets', function (Blueprint $table) {
            // Clasificación del cierre, para agrupar en informes. Se guarda la
            // clave de config('helpdesktickets.close_root_causes').
            $table->string('close_root_cause', 60)->nullable()->after('close_reason');
            $table->text('close_summary')->nullable()->after('close_root_cause');
            // Al cerrar se manda SIEMPRE la encuesta de satisfacción
            // (UpdateTicketOnClose). Hay cierres donde preguntar sobra —
            // spam, duplicados, un ticket abierto por error — y hasta ahora
            // no había forma de evitarlo desde la interfaz.
            $table->boolean('close_skip_survey')->default(false)->after('close_summary');
        });

        Schema::connection('helpdesk')->table('helpdesk_ticket_watchers', function (Blueprint $table) {
            $table->boolean('notify_customer_replies')->default(true)->after('user_id');
            $table->boolean('notify_internal_notes')->default(true)->after('notify_customer_replies');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_tickets', function (Blueprint $table) {
            $table->dropColumn(['close_root_cause', 'close_summary', 'close_skip_survey']);
        });

        Schema::connection('helpdesk')->table('helpdesk_ticket_watchers', function (Blueprint $table) {
            $table->dropColumn(['notify_customer_replies', 'notify_internal_notes']);
        });
    }
};
