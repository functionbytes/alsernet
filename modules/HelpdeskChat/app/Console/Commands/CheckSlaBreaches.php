<?php

namespace Modules\HelpdeskChat\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskChat\Services\Sla\SlaTracker;

class CheckSlaBreaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:check-breaches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for SLA breaches and mark conversation that have exceeded their SLA targets';

    /**
     * Execute the console command.
     */
    public function handle(SlaTracker $slaTracker): int
    {
        $this->info('Checking for SLA breaches...');

        $breachCount = $slaTracker->checkBreaches();

        if ($breachCount > 0) {
            $this->warn("Found {$breachCount} new SLA breaches");
        } else {
            $this->info('No new SLA breaches found');
        }

        return self::SUCCESS;
    }
}
