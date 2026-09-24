<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Esperando cliente" detiene el reloj del SLA (24-sep-2026). Ningún estado
 * tenía stops_sla_timer activado, así que un ticket a la espera de que el
 * cliente respondiera seguía consumiendo su plazo de resolución y aparecía
 * vencido sin que el equipo pudiera hacer nada. Al pasar a ese estado
 * TicketUpdateService pausa el SLA y lo reanuda al salir (o cuando el
 * cliente responde por el portal).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        DB::connection($this->connection)->table('helpdesk_ticket_statuses')
            ->where('slug', 'waiting-customer')
            ->update(['stops_sla_timer' => true]);
    }

    public function down(): void
    {
        DB::connection($this->connection)->table('helpdesk_ticket_statuses')
            ->where('slug', 'waiting-customer')
            ->update(['stops_sla_timer' => false]);
    }
};
