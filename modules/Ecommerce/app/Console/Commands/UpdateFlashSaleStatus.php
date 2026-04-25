<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Modules\Ecommerce\Models\FlashSale;

class UpdateFlashSaleStatus extends Command
{
    protected $signature = 'ecommerce:update-flash-sales';

    protected $description = 'Update flash sale statuses based on dates';

    public function handle(): void
    {
        FlashSale::query()
            ->where('status', 'published')
            ->where('end_date', '<', now())
            ->update(['status' => 'finished']);

        $this->info('Flash sales updated successfully.');
    }
}
