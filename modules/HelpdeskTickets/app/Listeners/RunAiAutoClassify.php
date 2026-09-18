<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Services\TicketAiService;

class RunAiAutoClassify implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Cola de IA, no 'default': autoClassify() llama al proveedor de LLM y
     * puede tardar decenas de segundos. En 'default' —que comparte worker con
     * PDFs de etiquetas, exports y notificaciones broadcast— cada clasificación
     * lenta retrasaba en fila todo lo demás. 'helpdesk-ai' existe justo para
     * esto y la sirve webadmin-worker-helpdesk (timeout 300).
     */
    public string $queue = 'helpdesk-ai';

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(private readonly TicketAiService $aiService) {}

    public function handle(TicketCreated $event): void
    {
        $this->aiService->autoClassify($event->ticket);
    }

    public function failed(TicketCreated $event, \Throwable $exception): void
    {
        Log::error('RunAiAutoClassify listener failed', [
            'ticket_id' => $event->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
