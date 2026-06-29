<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Seo\Jobs\CheckBrokenLinksJob;

class CheckBrokenLinksCommand extends Command
{
    protected $signature = 'seo:check-broken-links {--sync : Run synchronously instead of queuing}';

    protected $description = 'Check all canonical URLs for broken links (4xx/5xx responses)';

    public function handle(): int
    {
        if ($this->option('sync')) {
            $this->info('Running broken link check synchronously...');
            dispatch_sync(new CheckBrokenLinksJob);
            $this->info('Done.');
        } else {
            CheckBrokenLinksJob::dispatch();
            $this->info('Broken link check job dispatched to queue.');
        }

        return self::SUCCESS;
    }
}
