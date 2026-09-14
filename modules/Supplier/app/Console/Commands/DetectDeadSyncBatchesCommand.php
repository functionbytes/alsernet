<?php

namespace Modules\Supplier\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncStatus;

class DetectDeadSyncBatchesCommand extends Command
{
    protected $signature = 'supplier:sync:detect-dead-batches
                            {--minutes=120 : Minutes since last update to consider a batch dead}
                            {--fix : Actually mark dead batches as failed}';

    protected $description = 'Detect and optionally fix sync batches stuck in running state';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $shouldFix = $this->option('fix');

        $deadBatches = SyncBatch::dead($minutes)->get();

        if ($deadBatches->isEmpty()) {
            $this->info('No dead batches found.');

            return self::SUCCESS;
        }

        $this->warn("Found {$deadBatches->count()} dead batch(es) (running > {$minutes} min without update):");
        $this->table(
            ['ID', 'Name', 'Type', 'Started', 'Last Update'],
            $deadBatches->map(fn (SyncBatch $b) => [
                $b->id,
                $b->batch_name,
                $b->sync_type_name,
                $b->started_at?->format('Y-m-d H:i:s') ?? '—',
                $b->updated_at?->format('Y-m-d H:i:s') ?? '—',
            ])->toArray()
        );

        if (! $shouldFix) {
            $this->line('Run with --fix to mark them as failed.');

            return self::SUCCESS;
        }

        $fixed = 0;
        foreach ($deadBatches as $batch) {
            $batch->markAsFailed();
            $batch->update([
                'metadata' => array_merge($batch->metadata ?? [], [
                    'marked_as_dead_at' => now()->toIso8601String(),
                    'marked_as_dead_reason' => "No updates for {$minutes} minutes",
                ]),
            ]);

            // Also close any SyncStatus rows that are still 'running' for this batch,
            // otherwise the dashboard shows orphaned running statuses forever.
            SyncStatus::where('batch_id', $batch->id)
                ->where('status', 'running')
                ->update(['status' => 'failed', 'completed_at' => now()]);

            $fixed++;

            Log::warning('Sync batch marked as dead', [
                'batch_id' => $batch->id,
                'batch_name' => $batch->batch_name,
                'minutes_stuck' => $minutes,
            ]);
        }

        $this->info("{$fixed} batch(es) marked as failed.");

        return self::SUCCESS;
    }
}
