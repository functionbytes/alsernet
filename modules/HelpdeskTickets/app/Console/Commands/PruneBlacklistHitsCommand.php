<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklistHit;

class PruneBlacklistHitsCommand extends Command
{
    protected $signature = 'helpdesk:prune-blacklist-hits {--days=180 : Delete blacklist hits older than this many days}';

    protected $description = 'Permanently delete old rows from helpdesk_ticket_email_blacklist_hits (audit trail, grows unbounded otherwise)';

    public function handle(): int
    {
        try {
            $days = (int) $this->option('days');
            $count = 0;

            // Borrado directo (no forceDelete uno a uno): el modelo no usa
            // SoftDeletes, y es un historial de auditoría puro sin relaciones
            // hijas que limpiar — a diferencia de CleanupTrashedTicketsCommand.
            $hits = TicketEmailBlacklistHit::where('created_at', '<', now()->subDays($days))->cursor();

            foreach ($hits as $hit) {
                $hit->delete();
                $count++;
            }

            $this->info("Deleted {$count} blacklist hit(s) older than {$days} days.");
            Log::info('PruneBlacklistHits: hits purgados.', ['count' => $count, 'days' => $days]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('PruneBlacklistHits command failed', ['error' => $e->getMessage()]);
            $this->error('Command failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
