<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Services\AI\AutoTagService;

class AutoTagFirstMessage implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 2;

    public array $backoff = [15, 60];

    public function __construct(
        private readonly AutoTagService $autoTagService
    ) {}

    public function handle(ConversationMessageCreated $event): void
    {
        $item = $event->item;

        // Only process first incoming customer message
        if (! $item->isFromCustomer() || $item->is_internal) {
            return;
        }

        $conversation = $item->conversation;

        if (! $conversation) {
            return;
        }

        // Only auto-tag on the very first customer message
        $incomingCount = $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->whereNotNull('author_id')
            ->count();

        if ($incomingCount > 1) {
            return;
        }

        $this->autoTagService->categorizeAndAttach($conversation);
    }

    public function failed(ConversationMessageCreated $event, \Throwable $exception): void
    {
        Log::error('AutoTagFirstMessage failed', [
            'item_id' => $event->item->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
