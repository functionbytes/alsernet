<?php

namespace Modules\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Models\SeoAuditLog;

class CleanupOldAuditLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public int $backoff = 60;

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
        return [new WithoutOverlapping('seo:cleanup-audit-logs')];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoff = now()->subDays(config('Seo.cleanup.audit_logs_after_days', 180));
        $keep = config('Seo.cleanup.keep_audit_records_per_meta', 5);

        $deletedOld = SeoAuditLog::query()
            ->where('audited_at', '<', $cutoff)
            ->delete();

        $deletedExcess = $this->deleteExcessRecordsPerMeta($keep);

        $total = $deletedOld + $deletedExcess;

        Log::info("SEO cleanup: deleted {$total} old audit log records ({$deletedOld} by age, {$deletedExcess} excess per meta).");
    }

    /**
     * Delete records beyond the keep limit per seo_meta_id.
     */
    private function deleteExcessRecordsPerMeta(int $keep): int
    {
        $deleted = 0;

        SeoAuditLog::query()
            ->select('seo_meta_id')
            ->groupBy('seo_meta_id')
            ->havingRaw('COUNT(*) > ?', [$keep])
            ->pluck('seo_meta_id')
            ->each(function (int $metaId) use ($keep, &$deleted) {
                $keepIds = SeoAuditLog::query()
                    ->where('seo_meta_id', $metaId)
                    ->orderBy('audited_at', 'desc')
                    ->limit($keep)
                    ->pluck('id');

                $count = SeoAuditLog::query()
                    ->where('seo_meta_id', $metaId)
                    ->whereNotIn('id', $keepIds)
                    ->delete();

                $deleted += $count;
            });

        return $deleted;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupOldAuditLogs failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
