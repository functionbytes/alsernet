<?php

namespace Modules\Page\Services;

use Modules\Page\Jobs\DispatchPageWebhookJob;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageWebhook;

class PageWebhookService
{
    public function dispatch(string $event, Page $page): void
    {
        $payload = [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'status' => $page->status,
            'url' => $page->url ?? null,
            'published_at' => $page->published_at?->toIso8601String(),
            'updated_at' => $page->updated_at->toIso8601String(),
        ];

        PageWebhook::active()
            ->whereJsonContains('events', $event)
            ->get()
            ->each(fn ($webhook) => DispatchPageWebhookJob::dispatch($webhook->id, $event, $payload));
    }
}
