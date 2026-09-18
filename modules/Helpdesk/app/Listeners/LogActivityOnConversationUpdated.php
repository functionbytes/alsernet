<?php

namespace Modules\Helpdesk\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Services\Conversations\ActivityMessageService;

class LogActivityOnConversationUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function __construct(
        private readonly ActivityMessageService $service
    ) {}

    public function handle(ConversationUpdated $event): void
    {
        $conversation = $event->conversation;
        $by = $event->byUserId ? User::find($event->byUserId) : null;

        $this->logPriorityIfChanged($conversation, $by);
    }

    private function logPriorityIfChanged($conversation, $by): void
    {
        // getChanges() gives dirty state set before the event was dispatched.
        // The observer fires ConversationUpdated only when priority actually changed.
        $changes = $conversation->getChanges();

        if (! array_key_exists('priority', $changes)) {
            return;
        }

        $this->service->logPriorityChanged(
            $conversation,
            $conversation->getOriginal('priority'),
            $changes['priority'],
            $by
        );
    }

    public function failed(ConversationUpdated $event, \Throwable $exception): void
    {
        Log::error('LogActivityOnConversationUpdated failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
