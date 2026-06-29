<?php

namespace Modules\Mailrelay\Console\Commands;

use Illuminate\Console\Command;
use Modules\Mailrelay\Jobs\SyncCampaignStatsJob;

/**
 * Artisan command to sync campaign statistics from mail providers.
 *
 * Usage:
 *   php artisan mailrelay:sync-stats          # Dispatch to queue
 *   php artisan mailrelay:sync-stats --sync   # Run inline (synchronously)
 */
class SyncCampaignStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailrelay:sync-stats
                            {--sync : Run the job synchronously instead of dispatching to queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync campaign statistics from mail providers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('sync')) {
            $this->info('Running campaign stats sync inline...');

            try {
                app()->call([new SyncCampaignStatsJob, 'handle']);
                $this->info('Campaign stats sync completed successfully.');
            } catch (\Exception $e) {
                $this->error('Campaign stats sync failed: '.$e->getMessage());

                return Command::FAILURE;
            }

            return Command::SUCCESS;
        }

        SyncCampaignStatsJob::dispatch();
        $this->info('Campaign stats sync job dispatched to queue.');

        return Command::SUCCESS;
    }
}
