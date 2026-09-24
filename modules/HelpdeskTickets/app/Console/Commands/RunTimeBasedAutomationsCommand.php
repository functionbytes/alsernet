<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\AutomationEngine;

/**
 * Reglas por tiempo ("Periódicamente"): p. ej. "si lleva 48 h sin actividad
 * y está Esperando cliente → cerrar" o "si lleva 4 h sin respuesta y es
 * urgente → avisar al agente". Antes solo existían comandos fijos (auto-
 * cierre, vencidos) y el escalado por umbrales de configuración.
 */
class RunTimeBasedAutomationsCommand extends Command
{
    protected $signature = 'ticket:run-time-automations';

    protected $description = 'Evalúa las automatizaciones de tickets con disparador periódico';

    public function handle(AutomationEngine $engine): int
    {
        $automations = Automation::query()
            ->where('trigger_event', 'ticket.time_elapsed')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        if ($automations->isEmpty()) {
            return Command::SUCCESS;
        }

        $ran = 0;

        Ticket::query()
            ->whereNull('closed_at')
            ->notSnoozed()
            ->with(['status', 'customer'])
            ->chunkById(200, function ($tickets) use ($automations, $engine, &$ran) {
                foreach ($tickets as $ticket) {
                    try {
                        $ran += $engine->runTimeBased($automations, $ticket);
                    } catch (\Throwable $e) {
                        Log::error('RunTimeBasedAutomations: fallo en un ticket', [
                            'ticket_id' => $ticket->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Reglas por tiempo ejecutadas: {$ran}.");

        return Command::SUCCESS;
    }
}
