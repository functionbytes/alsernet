<?php

namespace Modules\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Models\Seo404Log;

class CleanupOld404Logs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('seo-scheduled');
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('seo:cleanup-404-logs')];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoff = now()->subDays(config('Seo.cleanup.404_logs_after_days', 90));

        $deleted = Seo404Log::query()
            ->where('created_at', '<', $cutoff)
            ->where('has_redirect', false)
            ->where(function ($query) {
                $query->whereNotNull('resolved_at')
                    ->orWhere('hit_count', 1);
            })
            ->delete();

        Log::info("SEO cleanup: deleted {$deleted} old 404 log records.");
    }
}
