<?php

namespace Modules\Page\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageCacheService;

class WarmPageCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Page $page
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        if (!PageCacheService::isEnabled()) {
            return;
        }

        PageCacheService::warm($this->page);
    }
}
