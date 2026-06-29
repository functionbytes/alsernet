<?php

namespace Modules\Engagement\Console\Commands;

use Illuminate\Console\Command;
use Modules\Engagement\Jobs\ArchiveOldEventsJob;

class ArchiveEventsCommand extends Command
{
    protected $signature = 'engagement:archive-events
                            {--days=90 : Events older than N days to archive}';

    protected $description = 'Archive engagement events older than specified days.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Archiving events older than {$days} days...");

        ArchiveOldEventsJob::dispatch($days);

        $this->info('Archive job dispatched.');

        return self::SUCCESS;
    }
}
