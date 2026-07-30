<?php

namespace Modules\Helpdesk\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Events\ConversationEscalated;
use Modules\Helpdesk\Notifications\EscalationNotification;

class SendEscalationNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications-high';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function handle(ConversationEscalated $event): void
    {
        $conversation = $event->conversation;

        $recipients = User::role('helpdesk-manager')->get();

        if ($recipients->isEmpty()) {
            $recipients = User::role('helpdesk-agent')->get();
        }

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new EscalationNotification($conversation, $event->reason));
    }

    public function failed(ConversationEscalated $event, \Throwable $exception): void
    {
        Log::error('SendEscalationNotification failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'reason' => $event->reason,
            'error' => $exception->getMessage(),
        ]);
    }
}
