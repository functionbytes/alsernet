<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationUnsnoozed;
use Modules\Helpdesk\Services\Conversations\ActivityMessageService;

class LogActivityOnConversationUnsnoozed implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function __construct(
        private readonly ActivityMessageService $service
    ) {}

    public function handle(ConversationUnsnoozed $event): void
    {
        $this->service->logUnsnoozed($event->conversation);
    }

    public function failed(ConversationUnsnoozed $event, \Throwable $exception): void
    {
        Log::error('LogActivityOnConversationUnsnoozed failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
