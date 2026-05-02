<?php

namespace Modules\Remarketing\Console\Commands;

use Illuminate\Console\Command;
use Modules\Remarketing\Jobs\MarkAbandonedCartsJob;

class MarkAbandonedCartsCommand extends Command
{
    protected $signature = 'remarketing:mark-abandoned-carts';

    protected $description = 'Detect abandoned carts and trigger automations';

    public function handle(): int
    {
        MarkAbandonedCartsJob::dispatch();
        $this->info('Abandoned carts job dispatched');

        return self::SUCCESS;
    }
}
