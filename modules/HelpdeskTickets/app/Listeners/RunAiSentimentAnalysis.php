<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Services\TicketSentimentService;

/**
 * Sella el sentimiento del cliente en cada mensaje entrante.
 *
 * Delega en TicketSentimentService, que intenta el LLM y cae a las listas de
 * palabras si no hay agente configurado. Antes llamaba al heuristico directo,
 * asi que un cliente que escribiera en frances o aleman salia siempre
 * "neutral" — sus listas solo cubren espanol e ingles.
 */
class RunAiSentimentAnalysis implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(private readonly TicketSentimentService $sentiment) {}

    public function handle(MessageAdded $event): void
    {
        $message = $event->message;

        // Solo mensajes del cliente: notas internas y respuestas de agente no
        // dicen nada de su humor.
        if ($message->is_internal || $message->user_id) {
            return;
        }

        $item = TicketItem::query()
            ->where('ticket_id', $message->ticket_id)
            ->where('is_internal', false)
            ->latest()
            ->first();

        if (! $item) {
            return;
        }

        $this->sentiment->tagItem($item);
    }

    public function failed(MessageAdded $event, \Throwable $exception): void
    {
        Log::error('RunAiSentimentAnalysis listener failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
