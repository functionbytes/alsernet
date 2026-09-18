<?php

namespace Modules\Supplier\Console\Commands;

use Illuminate\Console\Command;
use Modules\Supplier\Jobs\SyncModelsJob;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncSchedule;
use Modules\Supplier\Models\Sync\SyncSetting;

class RunModelSyncCommand extends Command
{
    protected $signature = 'supplier:sync-models
                            {--provider= : ERP Provider ID (optional)}
                            {--schedule-uid= : UID del schedule que disparó esta ejecución}
                            {--limit= : Máximo de modelos a sincronizar (opcional)}
                            {--force : Fuerza la resincronización de modelos ya sincronizados}';

    protected $description = 'Sincroniza modelos desde el ERP';

    public function handle(): int
    {
        $providerId = $this->option('provider') ? (int) $this->option('provider') : null;
        $scheduleUid = $this->option('schedule-uid');

        $schedule = null;

        if ($scheduleUid) {
            $schedule = SyncSchedule::query()->where('uid', $scheduleUid)->first();

            if ($schedule && ! $schedule->is_enabled) {
                $this->warn('Sync de modelos deshabilitado');

                return Command::SUCCESS;
            }
        }

        $batch = SyncBatch::create([
            'batch_name' => 'Model Sync '.now()->format('Y-m-d H:i:s'),
            'sync_type' => 'model',
            'status' => 'pending',
            'priority' => 'normal',
            'triggered_by' => $schedule ? 'scheduled' : 'manual',
        ]);

        if ($schedule) {
            $schedule->update([
                'last_run_at' => now(),
                'last_run_status' => 'running',
                'last_batch_id' => $batch->id,
            ]);
        }

        // Leer settings globales de sync
        $storedSettings = SyncSetting::query()
            ->whereIn('key', [
                'filter_date_from', 'default_limit',
                'default_mode', 'default_force',
                'default_description_empty', 'default_web_filter',
                'default_skip_ai', 'default_dry_run', 'default_register_only',
            ])
            ->pluck('value', 'key')
            ->toArray();

        $globalDateFrom = $storedSettings['filter_date_from'] ?? config('supplier.erp_sync.filter_date_from');
        $globalLimit    = (int) ($storedSettings['default_limit'] ?? 0);

        // Merge con metadata del schedule (permite override por horario); fallback a defaults globales
        $meta             = $schedule?->metadata ?? [];
        $dateFrom         = ($meta['date_from'] ?? null) ?: ($globalDateFrom ?: null);
        $dateField        = $meta['date_field']        ?? 'creation';
        $descriptionEmpty = $meta['description_empty'] ?? (($storedSettings['default_description_empty'] ?? '1') !== '0');
        $webFilter        = $meta['web_filter']        ?? ($storedSettings['default_web_filter'] ?? '2');
        $skipAi           = (bool) ($meta['skip_ai']   ?? !empty($storedSettings['default_skip_ai']));
        $dryRun           = (bool) ($meta['dry_run']   ?? !empty($storedSettings['default_dry_run']));
        $registerOnly     = (bool) ($meta['register_only'] ?? !empty($storedSettings['default_register_only']));

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $limit = $limit ?? ($globalLimit > 0 ? $globalLimit : null);
        $force = $this->option('force') || !empty($storedSettings['default_force']);

        $mode = $meta['mode'] ?? ($storedSettings['default_mode'] ?? ($providerId ? 'legacy' : 'filter'));

        SyncModelsJob::dispatch(
            $batch,
            $providerId,
            $limit,
            $force,
            $mode,
            $dateFrom,
            $skipAi,
            $dryRun,
            null,   // erpModelId
            $descriptionEmpty,
            $webFilter,
            $registerOnly,
            $dateField,
        );

        $this->info("Sync de modelos iniciado (batch #{$batch->id})".($limit ? " con límite de {$limit} modelos" : ''));

        return Command::SUCCESS;
    }
}
