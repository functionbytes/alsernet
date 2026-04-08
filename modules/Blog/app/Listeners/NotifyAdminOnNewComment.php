<?php

namespace Modules\Blog\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Blog\Events\BlogCommentCreated;
use Modules\Blog\Notifications\NewBlogCommentNotification;

class NotifyAdminOnNewComment implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function handle(BlogCommentCreated $event): void
    {
        $adminEmail = setting('admin_email') ?: config('mail.from.address');

        if (! $adminEmail) {
            return;
        }

        $admin = User::query()->where('email', $adminEmail)->first();

        if (! $admin) {
            return;
        }

        $admin->notify(new NewBlogCommentNotification($event->comment));
    }
}
