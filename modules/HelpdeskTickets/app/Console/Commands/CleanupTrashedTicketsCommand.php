<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\TicketSettings;

class CleanupTrashedTicketsCommand extends Command
{
    protected $signature = 'trashedticket:autodelete {--days= : Override the configured retention period in days}';

    protected $description = 'Permanently delete soft-deleted tickets older than N days';

    public function handle(): int
    {
        try {
            $settings = app(TicketSettings::class);

            if (! $settings->boolean('trashed_ticket_autodelete', true)) {
                $this->info('Automatic deletion of trashed tickets is disabled in Helpdesk settings.');

                return Command::SUCCESS;
            }

            $optionDays = $this->option('days');
            $days = $optionDays !== null
                ? max(1, (int) $optionDays)
                : $settings->integer('trashed_ticket_delete_time', 30);
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
