<?php

namespace Modules\Supplier\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncFailure;
use Modules\Supplier\Services\ErpSyncService;
use Modules\Supplier\Services\ProviderSyncAgent;
use Modules\Supplier\Services\SyncStatusService;

/**
 * Sync Providers Job
 *
 * Queued job that runs the ProviderSyncAgent for a given SyncBatch.
 */
class SyncProvidersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    public function __construct(
        public SyncBatch $batch,
        public ?int $supplierId = null,
    ) {}

    public function backoff(): array
    {
        return [60, 180, 600];
    }

    public function handle(SyncStatusService $statusService, ErpSyncService $erpSyncService): void
    {
        Log::info('Provider sync job started', [
            'batch_id' => $this->batch->id,
            'supplier_id' => $this->supplierId,
            'attempt' => $this->attempts(),
        ]);

        try {
            $agent = new ProviderSyncAgent($this->batch, $statusService, $erpSyncService);

            if ($this->supplierId) {
                $agent->forSupplier($this->supplierId);
            }

            $result = $agent->execute();

            Log::info('Provider sync job completed', [
                'batch_id' => $this->batch->id,
                'result' => $result,
            ]);
        } catch (Exception $e) {
            $this->recordSyncFailure($e);

            Log::error('Provider sync job failed', [
                'batch_id' => $this->batch->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->batch->markAsFailed();

        Log::error('Provider sync job failed permanently', [
            'batch_id' => $this->batch->id,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        $this->recordSyncFailure($exception);
    }

    protected function recordSyncFailure(Exception $exception): void
    {
        try {
            SyncFailure::create([
                'batch_id' => $this->batch->id,
                'sync_type' => 'provider',
                'supplier_id' => $this->supplierId,
                'entity_id' => null,
                'erp_id' => null,
                'changed_data' => [],
                'context' => [
                    'job' => self::class,
                    'attempt' => $this->attempts(),
                ],
                'error_message' => $exception->getMessage(),
                'error_code' => $exception->getCode() ?: 'UNKNOWN',
                'retry_count' => 0,
                'max_retries' => $this->tries,
                'failure_status' => 'pending',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to record provider sync failure', [
                'original_error' => $exception->getMessage(),
                'recording_error' => $e->getMessage(),
            ]);
        }
    }
}
