<?php

namespace Modules\Helpdesk\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Notifications\MessageReceivedNotification;

class SendMessageReceivedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(MessageReceived $event): void
    {
        $conversation = $event->conversation;
        $message = $event->message;

        // Notify the assigned agent, or all agents if unassigned
        if ($conversation->assignee_id) {
            $recipients = User::where('id', $conversation->assignee_id)->get();
        } else {
            $recipients = User::role('helpdesk-agent')->get();
        }

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new MessageReceivedNotification($conversation, $message));
    }

    public function failed(MessageReceived $event, \Throwable $exception): void
    {
        Log::error('SendMessageReceivedNotification failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'message_id' => $event->message->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
