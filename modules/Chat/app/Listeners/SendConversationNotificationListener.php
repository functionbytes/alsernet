<?php

namespace Modules\Chat\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Notifications\NewMessage;

class SendConversationNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $conversation = $message->conversation;

        if (! $conversation || $message->message_type !== 'incoming') {
            return;
        }

        $assignee = $conversation->assignee;

        if (! $assignee) {
            return;
        }

        // Don't notify the sender
        if ($message->sender_type === get_class($assignee) && $message->sender_id === $assignee->id) {
            return;
        }

        $assignee->notify(new NewMessage($message, $conversation));
    }
}
