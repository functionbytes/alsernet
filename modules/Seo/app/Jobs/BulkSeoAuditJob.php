<?php

namespace Modules\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Services\SeoAuditService;

class BulkSeoAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(private readonly string $jobKey = 'bulk_seo_audit')
    {
        $this->onQueue('seo-audit');
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->jobKey))->dontRelease()];
    }

    public function handle(SeoAuditService $auditService): void
    {
        $progressKey = 'seo.bulk_audit.progress';
        $total = SeoMeta::count();
        $processed = 0;

        Cache::put($progressKey, [
            'status' => 'running',
            'processed' => 0,
            'total' => $total,
            'started_at' => now()->toIso8601String(),
        ], 7200);

        try {
            SeoMeta::orderBy('id')->chunk(50, function ($metas) use ($auditService, &$processed, $total, $progressKey) {
                foreach ($metas as $meta) {
                    try {
                        $auditService->auditMeta($meta);
                    } catch (\Throwable $e) {
                        Log::warning("BulkSeoAuditJob: failed to audit meta #{$meta->id}: {$e->getMessage()}");
                    }
                    $processed++;
                }

                Cache::put($progressKey, [
                    'status' => 'running',
                    'processed' => $processed,
                    'total' => $total,
                    'started_at' => Cache::get($progressKey)['started_at'],
                ], 7200);
            });

            Cache::put($progressKey, [
                'status' => 'completed',
                'processed' => $processed,
                'total' => $total,
                'completed_at' => now()->toIso8601String(),
            ], 3600);

            Log::info("BulkSeoAuditJob completed: {$processed}/{$total} metas audited.");
        } catch (\Throwable $e) {
            Cache::put($progressKey, [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'processed' => $processed,
                'total' => $total,
            ], 3600);

            throw $e;
        }
    }
}
