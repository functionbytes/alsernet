<?php

namespace Modules\Helpdesk\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationStatusChanged;
use Modules\Helpdesk\Notifications\StatusChangedNotification;

class SendStatusChangedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(ConversationStatusChanged $event): void
    {
        $conversation = $event->conversation;
        $newStatus = $event->newStatus;
        $notification = new StatusChangedNotification($conversation, $newStatus);

        // Notify the customer
        if ($conversation->customer) {
            $conversation->customer->notify($notification);
        }

        // Notify the assigned agent
        if ($conversation->assignee_id) {
            $agent = User::find($conversation->assignee_id);
            $agent?->notify($notification);
        }
    }

    public function failed(ConversationStatusChanged $event, \Throwable $exception): void
    {
        Log::error('SendStatusChangedNotification failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'new_status_id' => $event->newStatus->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
