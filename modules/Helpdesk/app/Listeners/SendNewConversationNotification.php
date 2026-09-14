<?php

namespace Modules\Helpdesk\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Notifications\NewConversationNotification;

class SendNewConversationNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(ConversationCreated $event): void
    {
        $conversation = $event->conversation;

        $recipients = User::role('helpdesk-agent')->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new NewConversationNotification($conversation));
    }

    public function failed(ConversationCreated $event, \Throwable $exception): void
    {
        Log::error('SendNewConversationNotification failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
