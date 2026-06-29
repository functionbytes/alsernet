<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationAssigned;
use Modules\Helpdesk\Services\Conversations\ActivityMessageService;

class LogActivityOnConversationAssigned implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function __construct(
        private readonly ActivityMessageService $service
    ) {}

    public function handle(ConversationAssigned $event): void
    {
        $this->service->logAssigned($event->conversation, $event->assignee);
    }

    public function failed(ConversationAssigned $event, \Throwable $exception): void
    {
        Log::error('LogActivityOnConversationAssigned failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
