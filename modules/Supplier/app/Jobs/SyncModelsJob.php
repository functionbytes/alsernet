<?php

namespace Modules\Supplier\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncSchedule;
use Modules\Supplier\Services\Integrations\ErpModelSyncService;
use Modules\Supplier\Services\SyncStatusService;

class SyncModelsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    /**
     * Keep the unique lock alive for 30 minutes — long enough that a stuck job
     * can't block forever, short enough that overlapping syncs are prevented.
     */
    public int $uniqueFor = 1800;

    /**
     * One sync per (provider, force) combo at a time. Prevents triple-click on
     * "Sincronizar" or scheduler overlap from running 3 Oracle syncs in parallel
     * over the same MODELO table.
     */
    public function uniqueId(): string
    {
        return 'sync_models:'.($this->erpProviderId ?? 'all').':'.($this->force ? 'force' : 'normal');
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        protected SyncBatch $batch,
        protected ?int $erpProviderId = null,
        protected ?int $limit = null,
        protected bool $force = false,
        protected string $mode = 'filter',
        protected ?string $dateFrom = null,
        protected bool $skipAi = false,
        protected bool $dryRun = false,
        protected ?int $erpModelId = null,
        protected bool $descriptionEmpty = false,
        protected ?string $webFilter = '2',
        protected bool $registerOnly = false,
        protected string $dateField = 'creation',
    ) {
        $this->onQueue('sync');
    }

    public function handle(ErpModelSyncService $syncService, SyncStatusService $statusService): void
    {
        $this->batch->refresh();
        if ($this->batch->isTerminal()) {
            Log::warning('SyncModelsJob skipped — batch already in terminal state', [
                'batch_id' => $this->batch->id,
                'status' => $this->batch->status,
            ]);
            return;
        }

        Log::info('SyncModelsJob started', [
            'batch_id' => $this->batch->id,
            'erp_provider_id' => $this->erpProviderId,
        ]);

        $status = $statusService->startSync(
            $this->batch,
            0,
            $this->batch->triggered_by ?? 'manual',
            $this->batch->filter_criteria
        );

        try {
            $result = $this->runSync($syncService);

            if ($result['success']) {
                $statusService->completeSync($status, [
                    'models_synced'     => $result['models'] ?? 0,
                    'attributes_synced' => $result['attributes'] ?? 0,
                    'models_skipped'    => $result['skipped'] ?? 0,
                ]);
                $this->updateSchedule('success');
                $this->notifyAdmin();

                Log::info('SyncModelsJob completed', [
                    'batch_id' => $this->batch->id,
                    'stats' => $result,
                ]);

                return;
            }

            $statusService->failSync($status, implode(', ', $result['errors'] ?? ['Unknown error']));
            $this->updateSchedule('failed');
            $this->notifyAdmin();

            Log::error('SyncModelsJob failed', [
                'batch_id' => $this->batch->id,
                'errors' => $result['errors'] ?? [],
            ]);
        } catch (Exception $e) {
            try {
                $statusService->failSync($status, $e->getMessage());
            } catch (Exception $inner) {
                Log::error('Failed to mark sync status as failed', ['error' => $inner->getMessage()]);
                $this->batch->markAsFailed();
                $this->notifyAdmin();
                // failSync falló: actualizar SyncStatus directamente para evitar que quede en 'running'
                try {
                    $status->update(['status' => 'failed', 'completed_at' => now()]);
                } catch (Exception) {}
            }

            $this->updateSchedule('failed');

            Log::error('SyncModelsJob exception', [
                'batch_id' => $this->batch->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array{success: bool, models: int, attributes: int, errors: array}
     */
    protected function runSync(ErpModelSyncService $syncService): array
    {
        return $syncService->syncAllModelsFromFilter(
            $this->batch,
            $this->limit,
            $this->force,
            $this->dateFrom,
            $this->skipAi,
            $this->dryRun,
            $this->erpModelId,
            $this->erpProviderId,
            $this->descriptionEmpty,
            $this->webFilter,
            $this->registerOnly,
            $this->dateField,
        );
    }

    public function failed(Exception $exception): void
    {
        $this->batch->markAsFailed();
        $this->notifyAdmin();
        $this->updateSchedule('failed');

        Log::error('SyncModelsJob permanently failed', [
            'batch_id' => $this->batch->id,
            'error' => $exception->getMessage(),
        ]);
    }

    protected function notifyAdmin(): void
    {
        $this->batch->refresh();

        $aiCost = $this->calcBatchAiCost();

        // Structured INFO log always — independent of trigger type
        Log::info('SyncModelsJob batch summary', [
            'batch_id'        => $this->batch->id,
            'batch_uid'       => $this->batch->uid,
            'batch_name'      => $this->batch->batch_name,
            'status'          => $this->batch->status,
            'processed_items' => $this->batch->processed_items,
            'failed_items'    => $this->batch->failed_items,
            'duration_s'      => $this->batch->duration_seconds,
            'triggered_by'    => $this->batch->triggered_by,
            'ai_cost_usd'     => round($aiCost, 6),
        ]);

        // In-app DB notification only for manually-triggered batches
        if ($this->batch->triggered_by !== 'manual') {
            return;
        }

        $admins = \App\Models\User::where('role', 'admin')->get();
        if ($admins->isEmpty()) {
            $first = \App\Models\User::first();
            if ($first) {
                $admins = collect([$first]);
            }
        }

        $notification = new \Modules\Supplier\Notifications\SyncBatchCompletedNotification($this->batch);
        foreach ($admins as $admin) {
            try {
                $admin->notify($notification);
            } catch (\Throwable $e) {
                Log::warning('SyncModelsJob: failed to notify admin', [
                    'user_id' => $admin->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    protected function calcBatchAiCost(): float
    {
        try {
            return (float) DB::table('supplier_ai_costs')
                ->where('batch_id', (string) $this->batch->id)
                ->selectRaw('SUM(COALESCE(input_cost,0) + COALESCE(output_cost,0) + COALESCE(web_search_cost,0)) as total')
                ->value('total') ?? 0.0;
        } catch (\Throwable) {
            return 0.0;
        }
    }

    protected function updateSchedule(string $status): void
    {
        SyncSchedule::where('sync_type', 'model')->update([
            'last_run_at' => now(),
            'last_run_status' => $status,
            'last_batch_id' => $this->batch->id,
        ]);
    }
}
