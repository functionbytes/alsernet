<?php

namespace Modules\Page\Observers;

use Modules\Page\Models\Page;
use Modules\Page\Services\PageCacheService;

class PageObserver
{
    /**
     * Handle the Page "updated" event.
     */
    public function updated(Page $page): void
    {
        // Clear the cache for this page
        PageCacheService::forget($page->slug);

        // If slug changed, also clear old slug cache
        if ($page->wasChanged('slug')) {
            PageCacheService::forget($page->getOriginal('slug'));
        }
    }

    /**
     * Handle the Page "deleted" event.
     */
    public function deleted(Page $page): void
    {
        // Clear the cache for deleted page
        PageCacheService::forget($page->slug);
    }

    /**
     * Handle the Page "restored" event.
     */
    public function restored(Page $page): void
    {
        // Recache the restored page
        if (PageCacheService::isEnabled()) {
            PageCacheService::set($page);
        }
    }

    /**
     * Handle the Page "forceDeleted" event.
     */
    public function forceDeleted(Page $page): void
    {
        // Clear the cache for permanently deleted page
        PageCacheService::forget($page->slug);
    }
}
