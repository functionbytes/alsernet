<?php

namespace Modules\HelpdeskSocial\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskSocial\Jobs\SyncCompetitorMetricsJob;

class SyncCompetitorMetricsCommand extends Command
{
    protected $signature = 'helpdesksocial:sync-competitors {--competitor-id= : Sync only a specific competitor}';

    protected $description = 'Sync competitor social metrics (simulated if no API available)';

    public function handle(): int
    {
        $competitorId = $this->option('competitor-id');

        SyncCompetitorMetricsJob::dispatch($competitorId ? (int) $competitorId : null);

        $this->info('Competitor metrics sync job dispatched'.($competitorId ? " for competitor ID: {$competitorId}" : ' for all competitors').'.');

        return self::SUCCESS;
    }
}
