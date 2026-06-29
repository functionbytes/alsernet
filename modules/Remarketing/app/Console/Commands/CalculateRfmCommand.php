<?php

namespace Modules\Remarketing\Console\Commands;

use Illuminate\Console\Command;
use Modules\Remarketing\Jobs\CalculateRfmJob;

class CalculateRfmCommand extends Command
{
    protected $signature = 'remarketing:calculate-rfm';

    protected $description = 'Recalculate RFM scores and trigger win-back automations';

    public function handle(): int
    {
        CalculateRfmJob::dispatch();
        $this->info('RFM job dispatched');

        return self::SUCCESS;
    }
}
