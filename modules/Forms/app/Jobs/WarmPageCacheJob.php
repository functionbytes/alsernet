<?php

namespace Modules\Forms\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageCacheService;

/**
 * Re-warma el cache de una Page después de que un form embebido
 * invalidara su caché. Disparado async por FormPageCacheInvalidator.
 */
class WarmPageCacheJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public int $backoff = 15;

    public function __construct(
        public readonly int $pageId,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        if (! class_exists(Page::class)
            || ! class_exists(PageCacheService::class)) {
            return;
        }

        $page = Page::find($this->pageId);

        if (! $page) {
            return;
        }

        try {
            PageCacheService::warm($page);
        } catch (\Throwable $e) {
            Log::warning('Forms: PageCache warm failed', [
                'page_id' => $this->pageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function uniqueId(): string
    {
        return 'forms:warm-page:'.$this->pageId;
    }
}
