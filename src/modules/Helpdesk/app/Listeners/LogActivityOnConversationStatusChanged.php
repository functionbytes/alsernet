<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationStatusChanged;
use Modules\Helpdesk\Services\Conversations\ActivityMessageService;

class LogActivityOnConversationStatusChanged implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function __construct(
        private readonly ActivityMessageService $service
    ) {}

    public function handle(ConversationStatusChanged $event): void
    {
        $this->service->logStatusChanged(
            $event->conversation,
            null,
            $event->newStatus->name,
            auth()->user()
        );
    }

    public function failed(ConversationStatusChanged $event, \Throwable $exception): void
    {
        Log::error('LogActivityOnConversationStatusChanged failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
