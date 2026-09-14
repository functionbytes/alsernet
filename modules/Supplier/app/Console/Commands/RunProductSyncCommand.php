<?php

namespace Modules\Supplier\Console\Commands;

use Illuminate\Console\Command;
use Modules\Supplier\Jobs\SyncProviderProductsJob;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncSchedule;

class RunProductSyncCommand extends Command
{
    protected $signature = 'supplier:sync-products
                            {--provider= : ERP Provider ID (optional)}
                            {--schedule-uid= : UID del schedule que disparó esta ejecución}
                            {--limit= : Máximo de productos a sincronizar (opcional)}';

    protected $description = 'Sincroniza productos desde el ERP';

    public function handle(): int
    {
        $providerId = $this->option('provider') ? (int) $this->option('provider') : null;
        $scheduleUid = $this->option('schedule-uid');

        $schedule = null;

        if ($scheduleUid) {
            $schedule = SyncSchedule::query()->where('uid', $scheduleUid)->first();

            if ($schedule && ! $schedule->is_enabled) {
                $this->warn('Sync de productos deshabilitado');

                return Command::SUCCESS;
            }
        }

        $batch = SyncBatch::create([
            'batch_name' => 'Product Sync '.now()->format('Y-m-d H:i:s'),
            'sync_type' => 'product',
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

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        SyncProviderProductsJob::dispatch($batch, $providerId, $limit);

        $this->info("Sync de productos iniciado (batch #{$batch->id})".($limit ? " con límite de {$limit} productos" : ''));

        return Command::SUCCESS;
    }
}
