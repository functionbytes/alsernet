<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "En espera" también detiene el reloj del SLA (24-sep-2026), igual que
 * "Esperando cliente" desde 2026_09_24_000005. Un ticket aparcado a la
 * espera de un proveedor o de otro departamento seguía consumiendo su plazo
 * y aparecía vencido sin que el agente pudiera hacer nada.
 *
 * Además se pausan ya los tickets que están ahora mismo en cualquiera de los
 * dos estados: el flag solo actúa al CAMBIAR de estado (TicketUpdateService),
 * así que sin esto los que ya estaban aparcados seguirían contando hasta que
 * alguien los moviera. down() no los reanuda: no se sabe cuáles estaban
 * pausados antes, y reanudar desplaza los vencimientos por el tiempo pausado.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $db = DB::connection($this->connection);

        $db->table('helpdesk_ticket_statuses')
            ->where('slug', 'on-hold')
            ->update(['stops_sla_timer' => true]);

        $statusIds = $db->table('helpdesk_ticket_statuses')
            ->where('stops_sla_timer', true)
            ->pluck('id');

        if ($statusIds->isNotEmpty()) {
            $db->table('helpdesk_tickets')
                ->whereIn('status_id', $statusIds)
                ->whereNull('sla_paused_at')
                ->whereNull('closed_at')
                ->whereNull('resolved_at')
                ->whereNull('deleted_at')
                ->update(['sla_paused_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::connection($this->connection)->table('helpdesk_ticket_statuses')
            ->where('slug', 'on-hold')
            ->update(['stops_sla_timer' => false]);
    }
};
