<?php

namespace Modules\Page\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;
use Modules\Page\Notifications\PageUnpublishedNotification;

class UnpublishScheduledPagesJob implements ShouldQueue
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
        Log::info('Starting scheduled page unpublishing job');

        // Get all pages scheduled for unpublishing
        $pages = Page::scheduledForUnpublishing()
            ->with('user')
            ->get();

        Log::info("Found {$pages->count()} pages scheduled for unpublishing");

        $unpublishedCount = 0;
        $failedCount = 0;

        foreach ($pages as $page) {
            try {
                $this->unpublishPage($page);
                $unpublishedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error("Error unpublishing scheduled page {$page->id}: {$e->getMessage()}", [
                    'page_id' => $page->id,
                    'page_title' => $page->title,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('Scheduled page unpublishing completed', [
            'unpublished' => $unpublishedCount,
            'failed' => $failedCount,
        ]);
    }

    /**
     * Unpublish a scheduled page.
     */
    protected function unpublishPage(Page $page): void
    {
        DB::transaction(function () use ($page) {
            $page->update([
                'status' => PageStatus::Draft->value,
                'unpublish_at' => null,
            ]);
        });

        Log::info('Page unpublished automatically', [
            'page_id' => $page->id,
            'title' => $page->title,
        ]);

        if ($page->user) {
            try {
                $page->user->notify(new PageUnpublishedNotification($page, 'scheduled'));
            } catch (\Exception $e) {
                Log::error("Failed to send page unpublished notification: {$e->getMessage()}");
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UnpublishScheduledPagesJob failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
