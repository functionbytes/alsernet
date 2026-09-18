<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TicketsCrudController::lastMailTicketIds() resuelve los filtros
 * "mail_status"/"mail_type" del listado con una subconsulta correlacionada
 * (m.id = (SELECT MAX(m2.id) FROM helpdesk_ticket_mails m2 WHERE
 * m2.ticket_id = m.ticket_id)) dentro de un whereIn() que envuelve el
 * listado principal. El índice simple sobre ticket_id (migración original)
 * sirve para localizar las filas de cada ticket, pero el MAX(id) todavía
 * necesita tocar los datos; un índice compuesto (ticket_id, id) satisface
 * el MAX() directamente desde el índice — 14-sep-2026, auditoría de
 * rendimiento.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_mails', function (Blueprint $table) {
            $table->index(['ticket_id', 'id'], 'helpdesk_ticket_mails_ticket_id_id_index');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_mails', function (Blueprint $table) {
            $table->dropIndex('helpdesk_ticket_mails_ticket_id_id_index');
        });
    }
};
