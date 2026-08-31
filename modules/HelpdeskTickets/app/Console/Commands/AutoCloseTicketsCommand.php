<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;

class AutoCloseTicketsCommand extends Command
{
    protected $signature = 'ticket:autoclose {--days=7 : Close tickets resolved for this many days}';

    protected $description = 'Auto-close resolved tickets that have not had activity for N days';

    public function handle(): int
    {
        try {
            $closedStatus = TicketStatus::where('is_open', false)->orderBy('order')->first();

            if (! $closedStatus) {
                $this->error('No closed status found.');

                return Command::FAILURE;
            }

            $days = (int) $this->option('days');
            $resolvedStatus = TicketStatus::where('name', 'like', '%resolv%')->first();

            $query = Ticket::query()
                ->whereNull('closed_at')
                ->where('updated_at', '<', now()->subDays($days));

            if ($resolvedStatus) {
                $query->where('status_id', $resolvedStatus->id);
            }

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
                    // TicketUpdateService::applyChanges() (el único lugar que
                    // dispara este evento) — SendCustomerStatusNotification
                    // nunca corría para un auto-cierre.
                    broadcast(new TicketStatusChanged($ticket, $oldStatus, $closedStatus));

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
