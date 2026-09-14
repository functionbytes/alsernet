<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;

class CleanupTrashedTicketsCommand extends Command
{
    protected $signature = 'trashedticket:autodelete {--days=30 : Delete tickets trashed for this many days}';

    protected $description = 'Permanently delete soft-deleted tickets older than N days';

    public function handle(): int
    {
        try {
            $days = (int) $this->option('days');
            $count = 0;

            $tickets = Ticket::onlyTrashed()
                ->where('deleted_at', '<', now()->subDays($days))
                ->cursor();

            foreach ($tickets as $ticket) {
                try {
                    $ticket->forceDelete();
                    $count++;
                } catch (\Throwable $e) {
                    Log::error('CleanupTrashedTickets failed for ticket', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Permanently deleted {$count} trashed ticket(s).");
            Log::info('CleanupTrashedTickets: tickets permanently deleted.', ['count' => $count, 'days' => $days]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('CleanupTrashedTickets command failed', ['error' => $e->getMessage()]);
            $this->error('Command failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
