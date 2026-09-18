<?php

namespace Modules\Supplier\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncSchedule;
use Modules\Supplier\Services\Integrations\ErpProviderProductSyncService;
use Modules\Supplier\Services\SyncStatusService;

class SyncProviderProductsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    public int $uniqueFor = 1800;

    public function uniqueId(): string
    {
        return 'sync_provider_products:'.($this->erpProviderId ?? 'all');
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    protected SyncBatch $batch;

    protected ?int $erpProviderId;

    protected ?int $limit;

    protected array $filterCriteria;

    public function __construct(SyncBatch $batch, ?int $erpProviderId = null, ?int $limit = null, array $filterCriteria = [])
    {
        $this->batch = $batch;
        $this->erpProviderId = $erpProviderId;
        $this->limit = $limit;
        $this->filterCriteria = $filterCriteria ?: ($batch->filter_criteria ?? []);
    }

    public function handle(ErpProviderProductSyncService $syncService, SyncStatusService $statusService): void
    {
        $this->batch->refresh();
        if ($this->batch->isTerminal()) {
            Log::warning('SyncProviderProductsJob skipped — batch already in terminal state', [
                'batch_id' => $this->batch->id,
                'status' => $this->batch->status,
            ]);
            return;
        }

        $status = null;

        try {
            Log::info('SyncProviderProductsJob started', [
                'batch_id' => $this->batch->id,
                'erp_provider_id' => $this->erpProviderId,
            ]);

            $status = $statusService->startSync($this->batch, 0, $this->batch->triggered_by ?? 'manual', $this->batch->filter_criteria);

            // Extract filter criteria with defaults
            $dryRun = (bool) ($this->filterCriteria['dry_run'] ?? false);

            if ($dryRun) {
                Log::info('DRY-RUN mode enabled for product sync', ['batch_id' => $this->batch->id]);
            }

            if ($this->erpProviderId) {
                $result = $syncService->syncProviderProducts($this->erpProviderId, $this->batch, $this->limit, $this->filterCriteria);
            } else {
                $result = $syncService->syncAllProviderProducts($this->batch, $this->limit, $this->filterCriteria);
            }

            if ($result['success']) {
                $statusService->completeSync($status, [
                    'products_synced' => $result['products'] ?? 0,
                    'attributes_synced' => $result['attributes'] ?? 0,
                ]);
                $this->updateSchedule('success');

                Log::info('SyncProviderProductsJob completed', [
                    'batch_id' => $this->batch->id,
                    'stats' => $result,
                ]);
            } else {
                $statusService->failSync($status, implode(', ', $result['errors'] ?? ['Unknown error']));
                $this->updateSchedule('failed');

                Log::error('SyncProviderProductsJob failed', [
                    'batch_id' => $this->batch->id,
                    'errors' => $result['errors'] ?? [],
                ]);
            }
        } catch (Exception $e) {
            if (isset($status)) {
                try {
                    $statusService->failSync($status, $e->getMessage());
                } catch (Exception $inner) {
                    Log::error('Failed to mark sync status as failed', ['error' => $inner->getMessage()]);
                    $this->batch->markAsFailed();
                }
            } else {
                $this->batch->markAsFailed();
            }
            $this->updateSchedule('failed');

            Log::error('SyncProviderProductsJob exception', [
                'batch_id' => $this->batch->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->batch->markAsFailed();
        $this->updateSchedule('failed');

        Log::error('SyncProviderProductsJob permanently failed', [
            'batch_id' => $this->batch->id,
            'error' => $exception->getMessage(),
        ]);
    }

    protected function updateSchedule(string $status): void
    {
        SyncSchedule::where('sync_type', 'product')->update([
            'last_run_at' => now(),
            'last_run_status' => $status,
            'last_batch_id' => $this->batch->id,
        ]);
    }
}
