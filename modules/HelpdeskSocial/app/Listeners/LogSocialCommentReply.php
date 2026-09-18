<?php

namespace Modules\HelpdeskSocial\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Events\SocialCommentReplied;

class LogSocialCommentReply implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(SocialCommentReplied $event): void
    {
        $comment = $event->comment;

        Log::info('SocialCommentReplied', [
            'comment_id' => $comment->id,
            'platform' => $comment->platform,
            'reply_type' => $comment->reply_type,
            'replied_by_user_id' => $comment->replied_by_user_id,
        ]);
    }

    public function failed(SocialCommentReplied $event, \Throwable $exception): void
    {
        Log::error('LogSocialCommentReply failed', [
            'comment_id' => $event->comment->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
