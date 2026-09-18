<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Services\AI\SentimentService;

class AnalyzeSentimentOnIncoming implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 2;

    public array $backoff = [10, 30];

    public function __construct(
        private readonly SentimentService $sentimentService
    ) {}

    public function handle(ConversationMessageCreated $event): void
    {
        $item = $event->item;

        // Only analyze incoming customer messages (no user_id, has author_id)
        if (! $item->isFromCustomer() || $item->is_internal) {
            return;
        }

        $body = strip_tags($item->body ?? '');

        if (empty($body)) {
            return;
        }

        $sentiment = $this->sentimentService->detect($body);

        if ($sentiment['label'] !== 'negative') {
            return;
        }

        $conversation = $item->conversation;

        if (! $conversation) {
            return;
        }

        // Escalate priority to urgent
        if ($conversation->priority !== 'urgent') {
            $conversation->update(['priority' => 'urgent']);
        }

        // Attach sentiment_negative tag
        $this->attachSentimentTag($conversation);
    }

    public function failed(ConversationMessageCreated $event, \Throwable $exception): void
    {
        Log::error('AnalyzeSentimentOnIncoming failed', [
            'item_id' => $event->item->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }

    private function attachSentimentTag($conversation): void
    {
        $tag = ConversationTag::query()
            ->where('slug', 'sentiment-negative')
            ->first();

        if (! $tag) {
            $tag = ConversationTag::query()->create([
                'name' => 'Sentimiento negativo',
                'slug' => 'sentiment-negative',
                'color' => '#dc3545',
                'is_active' => true,
            ]);
        }

        if (! $conversation->conversationTags()->where('tag_id', $tag->id)->exists()) {
            $conversation->conversationTags()->attach($tag->id);
        }
    }
}
