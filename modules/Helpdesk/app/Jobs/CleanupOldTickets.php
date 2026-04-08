<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Ticket;

/**
 * Job to archive old closed tickets
 */
class CleanupOldTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    /** @var array<int> */
    public array $backoff = [300, 600];

    public function __construct()
    {
        $this->onQueue('helpdesk-scheduled');
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('cleanup-old-tickets'))->dontRelease()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('CleanupOldTickets job started at '.now());

            $daysThreshold = config('helpdesk.cleanup.closed_tickets_after_days', 365);
            $cutoffDate = now()->subDays($daysThreshold);

            $oldTickets = Ticket::query()
                ->where('status', 'closed')
                ->where('closed_at', '<', $cutoffDate)
                ->whereNull('deleted_at')
                ->get();

            $archivedCount = 0;

            foreach ($oldTickets as $ticket) {
                try {
                    $ticketId = $ticket->id;
                    $ticketSubject = $ticket->subject;
                    $closedAt = $ticket->closed_at;

                    $ticket->delete();

                    Log::info("Archived ticket #{$ticketId} - Subject: {$ticketSubject}", [
                        'ticket_id' => $ticketId,
                        'subject' => $ticketSubject,
                        'closed_at' => $closedAt,
                        'archived_at' => now(),
                    ]);

                    $archivedCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to archive ticket #{$ticket->id}: {$e->getMessage()}", [
                        'ticket_id' => $ticket->id,
                        'exception' => $e,
                    ]);
                }
            }

            Log::info('CleanupOldTickets job completed at '.now()." - Total tickets archived: {$archivedCount}", [
                'archived_count' => $archivedCount,
                'cutoff_date' => $cutoffDate,
                'days_threshold' => $daysThreshold,
            ]);
        } catch (\Exception $e) {
            Log::error("CleanupOldTickets job failed: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
