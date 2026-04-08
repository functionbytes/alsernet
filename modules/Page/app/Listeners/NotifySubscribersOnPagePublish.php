<?php

namespace Modules\Page\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Page\Events\PagePublishedForSubscribers;
use Modules\Page\Jobs\BulkNotifySubscribersJob;

class NotifySubscribersOnPagePublish implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PagePublishedForSubscribers $event): void
    {
        BulkNotifySubscribersJob::dispatch($event->page);
    }
}
