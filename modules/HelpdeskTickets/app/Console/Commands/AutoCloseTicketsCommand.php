<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketSettings;

class AutoCloseTicketsCommand extends Command
{
    protected $signature = 'ticket:autoclose {--days= : Override the configured inactivity period in days}';

    protected $description = 'Auto-close resolved tickets that have not had activity for N days';

    public function handle(): int
    {
        try {
            $settings = app(TicketSettings::class);

            if (! $settings->boolean('auto_close_ticket', true)) {
                $this->info('Automatic ticket closing is disabled in Helpdesk settings.');

                return Command::SUCCESS;
            }

            // Por slug, igual que Ticket::close(). Antes: el primer estado con
            // is_open=false por orden, que en la BD real es "En Espera" (no
            // "Cerrado"), y el resuelto se buscaba con LIKE '%resolv%', que no
            // casa con "Resuelto" — sin él, el WHERE por estado desaparecía y
            // el comando "cerraba" (a En Espera) CUALQUIER ticket inactivo.
            $closedStatus = TicketStatus::where('slug', 'closed')->first();
            $resolvedStatus = TicketStatus::where('slug', 'resolved')->first();

            if (! $closedStatus || ! $resolvedStatus) {
                $this->error('Missing "closed" or "resolved" ticket status (by slug); nothing closed.');

                return Command::FAILURE;
            }

            $optionDays = $this->option('days');
            $days = $optionDays !== null
                ? max(1, (int) $optionDays)
                : $settings->integer('auto_close_ticket_time', 30);
            $query = Ticket::query()
                // Sin esto, leer $ticket->status más abajo (para tenerlo
                // ANTES del update) disparaba una query lazy por ticket —
                // N+1 real con cientos de tickets elegibles para auto-cierre
                // (14-sep-2026, auditoría de rendimiento).
                ->with('status')
                ->whereNull('closed_at')
                ->where('status_id', $resolvedStatus->id)
                ->where('updated_at', '<', now()->subDays($days));

            $count = 0;

            foreach ($query->cursor() as $ticket) {
                try {
                    // Se guarda ANTES del update — $ticket->status ya no
                    // reflejaría el estado anterior después de update().
                    $oldStatus = $ticket->status;

                    $ticket->update([
                        'closed_at' => now(),
                        'status_id' => $closedStatus->id,
                    ]);

                    // Sin esto, el cliente nunca se enteraba de que su ticket
                    // se cerró solo por inactividad: este comando actualizaba
                    // status_id directo en el modelo, sin pasar por
                    // TicketUpdateService::applyChanges(). Y aunque se
                    // disparara, broadcast() (a diferencia de ::dispatch())
                    // SOLO envía por websocket -- nunca pasaba por el
                    // Dispatcher normal, así que SendCustomerStatusNotification
                    // (y los otros 3 listeners de TicketStatusChanged) nunca
                    // corrían para un auto-cierre (detectado 3-sep-2026).
                    TicketStatusChanged::dispatch($ticket, $oldStatus, $closedStatus);

                    $count++;
                } catch (\Throwable $e) {
                    Log::error('AutoClose failed for ticket', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Auto-closed {$count} tickets.");
            Log::info('AutoCloseTickets: closed tickets.', ['count' => $count, 'days' => $days]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('AutoCloseTickets command failed', ['error' => $e->getMessage()]);
            $this->error('Command failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
