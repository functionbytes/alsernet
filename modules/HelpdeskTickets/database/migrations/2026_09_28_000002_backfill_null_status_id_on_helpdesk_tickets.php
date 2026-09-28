<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reparación de datos (28-sep-2026): el catálogo de estados se quedó sin
 * ningún is_default=true (helpdesk_ticket_statuses), y hasta que
 * TicketObserver::creating() ganó el fallback al primer estado abierto
 * (ver esa migración/commit hermano), cualquier vía de alta que no fijara
 * status_id a mano creaba el ticket con status_id NULL — en local, 42
 * tickets así en el momento de escribir esta migración.
 *
 * Un ticket sin status_id no aparece en NINGÚN bucket de
 * Ticket::canonicalStatusSlug() (cae a 'open' solo cuando $status es null
 * en tiempo de ejecución, pero en BD queda huérfano de cualquier filtro por
 * status_id, p. ej. TicketsCrudController::applyQuickFilter()).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $connection = DB::connection($this->connection);

        $statusId = $connection->table('helpdesk_ticket_statuses')->where('is_default', true)->value('id')
            ?? $connection->table('helpdesk_ticket_statuses')->where('slug', 'new')->value('id')
            ?? $connection->table('helpdesk_ticket_statuses')->orderBy('order')->value('id');

        if ($statusId === null) {
            return;
        }

        $connection->table('helpdesk_tickets')
            ->whereNull('status_id')
            ->update(['status_id' => $statusId]);
    }

    public function down(): void
    {
        // Irreversible a propósito: no hay forma de distinguir qué filas
        // tenían status_id NULL antes de esta migración de las que ya
        // traían el mismo estado por otra vía.
    }
};
