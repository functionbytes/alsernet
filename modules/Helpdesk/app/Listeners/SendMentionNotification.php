<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\MentionDetected;
use Modules\Helpdesk\Notifications\MentionNotification;

class SendMentionNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications-high';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function handle(MentionDetected $event): void
    {
        $event->mentionedUser->notify(new MentionNotification(
            $event->conversation,
            $event->message,
            $event->mentionedByName,
        ));
    }

    public function failed(MentionDetected $event, \Throwable $exception): void
    {
        Log::error('SendMentionNotification failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'mentioned_user_id' => $event->mentionedUser->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
