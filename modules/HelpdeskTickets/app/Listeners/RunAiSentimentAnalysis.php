<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Services\TicketAiService;

class RunAiSentimentAnalysis implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(private readonly TicketAiService $aiService) {}

    public function handle(MessageAdded $event): void
    {
        $message = $event->message;

        // TicketMessage uses the `message` field; skip internal notes and agent messages
        if ($message->is_internal || $message->user_id) {
            return;
        }

        $result = $this->aiService->analyzeSentiment($message->message ?? '');

        // Find the most recent TicketItem for this ticket to update sentiment
        $item = TicketItem::query()
            ->where('ticket_id', $message->ticket_id)
            ->where('is_internal', false)
            ->latest()
            ->first();

        if (! $item) {
            return;
        }

        $item->update([
            'sentiment' => $result['sentiment'],
            'sentiment_score' => $result['score'],
        ]);

        $ticket = $item->ticket;

        if (! $ticket) {
            return;
        }

        $avg = $ticket->items()
            ->whereNotNull('sentiment_score')
            ->where('is_internal', false)
            ->avg('sentiment_score');

        $ticket->update(['customer_sentiment_avg' => $avg]);
    }

    public function failed(MessageAdded $event, \Throwable $exception): void
    {
        Log::error('RunAiSentimentAnalysis listener failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
