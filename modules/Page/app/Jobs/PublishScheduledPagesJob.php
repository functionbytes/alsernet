<?php

namespace Modules\Page\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Page\Models\Page;
use Modules\Page\Notifications\PagePublishedNotification;

class PublishScheduledPagesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('pages');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting scheduled page publishing job');

        // Get all pages scheduled for publishing
        $pages = Page::scheduledForPublishing()
            ->with('user')
            ->get();

        Log::info("Found {$pages->count()} pages scheduled for publishing");

        $publishedCount = 0;
        $failedCount = 0;

        foreach ($pages as $page) {
            try {
                $this->publishPage($page);
                $publishedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error("Error publishing scheduled page {$page->id}: {$e->getMessage()}", [
                    'page_id' => $page->id,
                    'page_title' => $page->title,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('Scheduled page publishing completed', [
            'published' => $publishedCount,
            'failed' => $failedCount,
        ]);
    }

    /**
     * Publish a scheduled page.
     */
    protected function publishPage(Page $page): void
    {
        $page->update([
            'status' => Page::STATUS_PUBLISHED,
            'published_at' => now(),
            'publish_at' => null,
        ]);

        Log::info('Page published automatically', [
            'page_id' => $page->id,
            'title' => $page->title,
        ]);

        if ($page->user) {
            try {
                $page->user->notify(new PagePublishedNotification($page, 'scheduled'));
            } catch (\Exception $e) {
                Log::error("Failed to send page published notification: {$e->getMessage()}");
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PublishScheduledPagesJob failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
