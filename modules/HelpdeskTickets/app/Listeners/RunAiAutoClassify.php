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

    public string $queue = 'default';

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
