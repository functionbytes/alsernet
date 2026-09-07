<?php

namespace Modules\HelpdeskAgents\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskAgents\Services\TicketEmbeddingService;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Indexa el vector de un ticket para la búsqueda de duplicados y la detección
 * de incidencias masivas.
 *
 * En cola y nunca en la petición: la llamada al proveedor de embeddings no
 * puede meterse en el camino de crear un ticket.
 */
class IndexTicketEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 10;

    public int $timeout = 60;

    public function __construct(public readonly int $ticketId) {}

    public function handle(TicketEmbeddingService $service): void
    {
        if (! config('helpdeskagents.ticket_similarity.enabled', false)) {
            return;
        }

        $ticket = Ticket::query()->find($this->ticketId);

        if ($ticket) {
            $service->index($ticket);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('IndexTicketEmbeddingJob failed', [
            'ticket_id' => $this->ticketId,
            'error' => $exception->getMessage(),
        ]);
    }
}
