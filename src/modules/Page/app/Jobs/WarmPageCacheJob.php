<?php

namespace Modules\Page\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageCacheService;

class WarmPageCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        private readonly Page $page
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        if (! PageCacheService::isEnabled()) {
            return;
        }

        PageCacheService::warm($this->page);
    }

    /**
     * Handle job final failure after all retries are exhausted.
     */
    public function failed(\Throwable $e): void
    {
        Log::warning('WarmPageCacheJob failed after all retries', [
            'page_id' => $this->page->id,
            'error' => $e->getMessage(),
        ]);
    }
}
