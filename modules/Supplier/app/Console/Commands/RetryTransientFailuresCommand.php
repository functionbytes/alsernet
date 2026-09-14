<?php

namespace Modules\Supplier\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\SupplierProductPrice;
use Modules\Supplier\Models\Sync\SyncFailure;
use Modules\Supplier\Services\ErpSyncService;
use Modules\Supplier\Services\Integrations\ErpModelSyncService;

/**
 * Auto-retry sync failures that look transient.
 *
 * Skips `sin_proveedor` (data-quality issue requiring Oracle fix) and any
 * failure whose retry_count is already at max. Applies a minimum cool-down
 * since last_retry_at to avoid hammering a broken upstream.
 */
class RetryTransientFailuresCommand extends Command
{
    protected $signature = 'supplier:retry-transient-failures
        {--limit=50 : Maximum failures to retry per run}
        {--min-age-minutes=15 : Minimum minutes since last_retry_at}';

    protected $description = 'Auto-retry transient sync failures (excludes sin_proveedor)';

    public function __construct(
        protected ErpSyncService $erpSyncService,
        protected ErpModelSyncService $erpModelSyncService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $minAge = (int) $this->option('min-age-minutes');

        $failures = SyncFailure::query()
            ->where('failure_status', 'pending')
            ->whereRaw('retry_count < max_retries')
            ->where('failure_type', '!=', 'sin_proveedor')
            ->where(function ($q) use ($minAge) {
                $q->whereNull('last_retry_at')
                    ->orWhere('last_retry_at', '<', now()->subMinutes($minAge));
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($failures->isEmpty()) {
            $this->info('No transient failures to retry.');

            return self::SUCCESS;
        }

        $stats = ['ok' => 0, 'failed' => 0];

        foreach ($failures as $failure) {
            $failure->increment('retry_count');
            $failure->update(['last_retry_at' => now()]);

            $result = match ($failure->sync_type) {
                'price' => $this->retryPriceSync($failure),
                'product' => $this->retryProductSync($failure),
                'provider' => $this->retryProviderSync($failure),
                default => ['success' => false, 'error' => "Unknown sync_type: {$failure->sync_type}"],
            };

            if ($result['success']) {
                // No se borra: se conserva en el histórico como "resuelto" para
                // no perder el rastro de qué ha fallado alguna vez.
                $failure->markAsResolved(null, 'Reintento automático exitoso');
                $stats['ok']++;
            } else {
                $failure->update(['error_message' => $result['error'] ?? 'retry failed']);
                $stats['failed']++;
            }
        }

        Log::info('Auto-retry of transient sync failures', [
            'attempted' => $failures->count(),
            'succeeded' => $stats['ok'],
            'failed' => $stats['failed'],
        ]);

        $this->info("Reintentados: {$failures->count()} | OK: {$stats['ok']} | Failed: {$stats['failed']}");

        return self::SUCCESS;
    }

    protected function retryPriceSync(SyncFailure $failure): array
    {
        try {
            $price = SupplierProductPrice::find($failure->entity_id);
            if (! $price) {
                return ['success' => false, 'error' => 'Price not found'];
            }
            $this->erpSyncService->syncPriceToOracle($price, array_keys($failure->changed_data ?? []));

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function retryProductSync(SyncFailure $failure): array
    {
        try {
            $result = $this->erpModelSyncService->retryModelFromErp($failure->erp_id);

            return $result['success']
                ? ['success' => true]
                : ['success' => false, 'error' => $result['error'] ?? 'retry failed'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function retryProviderSync(SyncFailure $failure): array
    {
        try {
            $provider = Supplier::find($failure->supplier_id);
            if (! $provider) {
                return ['success' => false, 'error' => 'Supplier not found'];
            }
            $this->erpSyncService->syncProviderToOracle($provider, array_keys($failure->changed_data ?? []));

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
