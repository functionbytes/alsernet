<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationTagAdded;
use Modules\Helpdesk\Services\Conversations\ActivityMessageService;

class LogActivityOnConversationTagAdded implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function __construct(
        private readonly ActivityMessageService $service
    ) {}

    public function handle(ConversationTagAdded $event): void
    {
        $this->service->logLabelAdded(
            $event->conversation,
            $event->tag->name,
            auth()->user()
        );
    }

    public function failed(ConversationTagAdded $event, \Throwable $exception): void
    {
        Log::error('LogActivityOnConversationTagAdded failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
