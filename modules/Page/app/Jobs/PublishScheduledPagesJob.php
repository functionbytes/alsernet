<?php

namespace Modules\Page\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Page\Models\Page;
use Modules\Page\Notifications\PagePublishedNotification;

class PublishScheduledPagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

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
        // Publish the page
        $page->status = Page::STATUS_PUBLISHED;
        $page->published_at = now();
        $page->save();

        Log::info('Page published automatically', [
            'page_id' => $page->id,
            'title' => $page->title,
            'publish_at' => $page->publish_at?->toDateTimeString(),
        ]);

        // Send notification to the page owner
        if ($page->user) {
            try {
                $page->user->notify(new PagePublishedNotification($page, 'scheduled'));
            } catch (\Exception $e) {
                Log::error("Failed to send page published notification: {$e->getMessage()}");
            }
        }

        // Clear publish_at after successful publishing
        $page->publish_at = null;
        $page->save();
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
