<?php

namespace Modules\HelpdeskSocial\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;

class BroadcastSocialComment implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function handle(SocialCommentReceived $event): void
    {
        Log::info('BroadcastSocialComment: comment received', [
            'comment_id' => $event->comment->id,
            'platform' => $event->comment->platform,
            'intent' => $event->comment->intent,
            'urgency' => $event->comment->urgency,
        ]);
    }

    public function failed(SocialCommentReceived $event, \Throwable $exception): void
    {
        Log::error('BroadcastSocialComment failed', [
            'comment_id' => $event->comment->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
