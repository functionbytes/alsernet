<?php

namespace Modules\Blog\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use Modules\Blog\Events\CommentApproved;
use Modules\Blog\Notifications\CommentApprovedNotification;

class NotifyCommenterOnApproval implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function handle(CommentApproved $event): void
    {
        $comment = $event->comment;

        if (! $comment->author_email || ! filter_var($comment->author_email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        Notification::route('mail', $comment->author_email)
            ->notify(new CommentApprovedNotification($comment));
    }
}
