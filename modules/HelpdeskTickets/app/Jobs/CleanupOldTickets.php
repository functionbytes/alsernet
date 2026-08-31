<?php

namespace Modules\HelpdeskTickets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Retira de la cola los tickets cerrados hace más de
 * helpdesk.cleanup.closed_tickets_after_days días.
 *
 * Es un soft delete (SoftDeletes), no un borrado físico: los tickets siguen en
 * la tabla con deleted_at y son recuperables desde la papelera. El docblock
 * decía "archive", que apunta a la columna is_archived — otra cosa distinta y
 * que este job no toca.
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

    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupOldTickets job permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
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

            // Bug real: este filtro era where('status', 'closed'). La tabla
            // helpdesk_tickets no tiene columna `status` — el estado es una FK
            // (status_id) al catálogo helpdesk_ticket_statuses. La consulta
            // lanzaba "Unknown column 'status'" en cada ejecución, así que el
            // job (programado a diario a las 02:00) nunca llegó a archivar
            // nada. whereNotNull('closed_at') es el criterio equivalente y
            // además está indexado. whereNull('deleted_at') era redundante:
            // SoftDeletes ya lo aplica.
            $oldTickets = Ticket::query()
                ->whereNotNull('closed_at')
                ->where('closed_at', '<', $cutoffDate)
                ->cursor();

            $removedCount = 0;

            foreach ($oldTickets as $ticket) {
                try {
                    $ticketId = $ticket->id;
                    $ticketSubject = $ticket->subject;
                    $closedAt = $ticket->closed_at;

                    $ticket->delete();

                    Log::info("Soft-deleted old ticket #{$ticketId} - Subject: {$ticketSubject}", [
                        'ticket_id' => $ticketId,
                        'subject' => $ticketSubject,
                        'closed_at' => $closedAt,
                        'deleted_at' => now(),
                    ]);

                    $removedCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to soft-delete ticket #{$ticket->id}: {$e->getMessage()}", [
                        'ticket_id' => $ticket->id,
                        'exception' => $e,
                    ]);
                }
            }

            Log::info('CleanupOldTickets job completed at '.now()." - Total tickets removed: {$removedCount}", [
                'removed_count' => $removedCount,
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
