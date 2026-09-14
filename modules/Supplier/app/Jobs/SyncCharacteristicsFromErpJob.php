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
use Modules\Supplier\Services\Integrations\ErpCharacteristicSyncService;
use Modules\Supplier\Services\SyncStatusService;

class SyncCharacteristicsFromErpJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public int $uniqueFor = 1800;

    public function uniqueId(): string
    {
        return 'sync_characteristics';
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    protected SyncBatch $batch;

    public function __construct(SyncBatch $batch)
    {
        $this->batch = $batch;
    }

    public function handle(ErpCharacteristicSyncService $syncService, SyncStatusService $statusService): void
    {
        $this->batch->refresh();
        if ($this->batch->isTerminal()) {
            Log::warning('SyncCharacteristicsFromErpJob skipped — batch already in terminal state', [
                'batch_id' => $this->batch->id,
                'status' => $this->batch->status,
            ]);

            return;
        }

        $status = null;

        try {
            Log::info('SyncCharacteristicsFromErpJob started', ['batch_id' => $this->batch->id]);

            $status = $statusService->startSync($this->batch, 0, $this->batch->triggered_by ?? 'manual', $this->batch->filter_criteria);

            $result = $syncService->syncAll($this->batch);

            if ($result['success']) {
                $statusService->completeSync($status, [
                    'characteristics_synced' => $result['characteristics']['synced'] ?? 0,
                    'values_synced' => $result['values']['synced'] ?? 0,
                ]);
                Log::info('SyncCharacteristicsFromErpJob completed', [
                    'batch_id' => $this->batch->id,
                    'stats' => $result,
                ]);
            } else {
                $statusService->failSync($status, implode(', ', $result['errors'] ?? ['Unknown error']));
                Log::error('SyncCharacteristicsFromErpJob failed', [
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

            Log::error('SyncCharacteristicsFromErpJob exception', [
                'batch_id' => $this->batch->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->batch->markAsFailed();

        Log::error('SyncCharacteristicsFromErpJob permanently failed', [
            'batch_id' => $this->batch->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
