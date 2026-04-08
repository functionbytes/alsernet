<?php

namespace Modules\Page\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Jobs\WarmPageCacheJob;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageCacheService;
use Modules\Page\Services\PageWebhookService;

class PageObserver
{
    public function __construct(
        private readonly PageWebhookService $webhookService,
    ) {}

    /**
     * Handle the Page "created" event.
     */
    public function created(Page $page): void
    {
        Cache::forget('pages:stats:counts');

        $this->webhookService->dispatch('page.created', $page);

        if (! config('Seo.auto_generate_description', true)) {
            return;
        }

        $description = Str::limit(strip_tags($page->content ?? ''), 160);

        if (filled($description)) {
            $page->updateSeoMeta(['description' => $description]);
        }
    }

    /**
     * Handle the Page "updated" event.
     */
    public function updated(Page $page): void
    {
        Cache::forget('pages:stats:counts');

        PageCacheService::forget($page->slug);

        if ($page->wasChanged('slug')) {
            PageCacheService::forget($page->getOriginal('slug'));
        }

        if ($page->status === PageStatus::Published) {
            $this->webhookService->dispatch('page.updated', $page);
        }

        if (! config('Seo.auto_generate_description', true) || $page->hasSeoMeta()) {
            $this->warmCacheIfPublished($page);

            return;
        }

        $description = Str::limit(strip_tags($page->content ?? ''), 160);

        if (filled($description)) {
            $page->updateSeoMeta(['description' => $description]);
        }

        $this->warmCacheIfPublished($page);
    }

    /**
     * Handle the Page "deleted" event.
     */
    public function deleted(Page $page): void
    {
        Cache::forget('pages:stats:counts');

        PageCacheService::forget($page->slug);

        $this->webhookService->dispatch('page.deleted', $page);
    }

    /**
     * Handle the Page "restored" event.
     */
    public function restored(Page $page): void
    {
        Cache::forget('pages:stats:counts');

        if (PageCacheService::isEnabled()) {
            PageCacheService::set($page);
        }
    }

    /**
     * Handle the Page "forceDeleted" event.
     */
    public function forceDeleted(Page $page): void
    {
        Cache::forget('pages:stats:counts');

        PageCacheService::forget($page->slug);

        $page->clearMediaCollection('featured');
        $page->clearMediaCollection('seo');
        $page->clearMediaCollection('gallery');
    }

    private function warmCacheIfPublished(Page $page): void
    {
        if (PageCacheService::isEnabled() && $page->status === PageStatus::Published) {
            dispatch(new WarmPageCacheJob($page))->delay(5);
        }
    }
}
