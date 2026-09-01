<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige datos históricos: antes de que TicketReplyMail/TicketMailable/
 * TicketComposedMail/TicketCreatedMail devolvieran Ticket::class (FQCN) desde
 * getEmailLogEntityType() (ver comentario en TicketComposedMail.php), algunas
 * filas quedaron con el string corto 'Ticket'/'ticket' en vez del FQCN — eso
 * rompe EmailLog::entityUrl() (config('entity_routes') indexa por FQCN) y
 * cualquier filtro por entity_type/entity_id. El código ya está arreglado
 * (todos los Mailables reales devuelven el FQCN); esta migración solo
 * normaliza las filas viejas, no cambia comportamiento nuevo.
 *
 * Deliberadamente NO toca filas con entity_type NULL de este módulo: sin
 * mailable_class tampoco poblado no hay forma fiable de saber a qué
 * corresponden, y "no tocar lo ambiguo" es el mismo criterio que ya sigue el
 * resto de correlación de este módulo (EmailBounceCorrelatorService).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        DB::table('email_logs')
            ->where('module', 'HelpdeskTickets')
            ->whereIn('entity_type', ['Ticket', 'ticket'])
            ->update(['entity_type' => 'Modules\\HelpdeskTickets\\Models\\Ticket']);
    }

    public function down(): void
    {
        // Intencionalmente no reversible: no hay forma de distinguir las filas
        // que esta migración normalizó de las que ya tenían el FQCN correcto
        // desde el principio, así que "deshacer" degradaría datos buenos.
    }
};
