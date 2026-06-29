<?php

namespace Modules\Page\Listeners;

use Modules\Page\Events\PagePublished;
use Modules\Page\Jobs\WarmPageCacheJob;

class WarmPageCacheOnPublish
{
    public function handle(PagePublished $event): void
    {
        WarmPageCacheJob::dispatch($event->page);
    }
}
