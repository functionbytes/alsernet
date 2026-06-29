<?php

namespace Modules\Blog\Listeners;

use Modules\Blog\Events\BlogPostPublished;
use Modules\Blog\Jobs\SendBlogPostNewsletterJob;

class SendNewsletterOnPublish
{
    public function handle(BlogPostPublished $event): void
    {
        if (! $event->post->send_newsletter) {
            return;
        }

        SendBlogPostNewsletterJob::dispatch($event->post->id)
            ->onQueue('newsletters');
    }
}
