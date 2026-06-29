<?php

namespace Modules\Blog\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Blog\Events\BlogCommentCreated;
use Modules\Blog\Notifications\NewCommentNotification;

class NotifyAuthorOnNewComment implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function handle(BlogCommentCreated $event): void
    {
        $comment = $event->comment;
        $post = $comment->post()->with('user')->first();
        $author = $post?->user;

        if (! $author) {
            return;
        }

        $author->notify(new NewCommentNotification($comment));
    }
}
