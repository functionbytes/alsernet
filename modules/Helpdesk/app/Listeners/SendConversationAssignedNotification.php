<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationAssigned;
use Modules\Helpdesk\Notifications\ConversationAssignedNotification;

class SendConversationAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(ConversationAssigned $event): void
    {
        $event->assignee->notify(new ConversationAssignedNotification($event->conversation));
    }

    public function failed(ConversationAssigned $event, \Throwable $exception): void
    {
        Log::error('SendConversationAssignedNotification failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'assignee_id' => $event->assignee->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
