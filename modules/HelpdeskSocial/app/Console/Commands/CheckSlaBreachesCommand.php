<?php

namespace Modules\HelpdeskSocial\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskSocial\Jobs\CheckSlaBreachesJob;

class CheckSlaBreachesCommand extends Command
{
    protected $signature = 'helpdesksocial:check-sla-breaches {--notify : Send breach notifications to assigned users}';

    protected $description = 'Check for SLA breaches and update breach flags';

    public function handle(): int
    {
        $notify = $this->option('notify');

        CheckSlaBreachesJob::dispatch($notify);

        $this->info('SLA breach check job dispatched'.($notify ? ' with notifications enabled' : '').'.');

        return self::SUCCESS;
    }
}
