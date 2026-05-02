<?php

namespace Modules\Remarketing\Console\Commands;

use Illuminate\Console\Command;
use Modules\Remarketing\Jobs\SyncCatalogJob;
use Modules\Remarketing\Jobs\SyncCustomersJob;
use Modules\Remarketing\Jobs\SyncOrdersJob;
use Modules\Remarketing\Models\Store;

class ReconcileCatalogCommand extends Command
{
    protected $signature = 'remarketing:reconcile-catalog';

    protected $description = 'Force a delta sync of catalog, customers and orders for active stores';

    public function handle(): int
    {
        $count = 0;

        Store::query()
            ->where('status', 'active')
            ->cursor()
            ->each(function (Store $store) use (&$count) {
                SyncCatalogJob::dispatch($store);
                SyncCustomersJob::dispatch($store);
                SyncOrdersJob::dispatch($store, now()->subDay());
                $count++;
            });

        $this->info("Reconcile dispatched for {$count} stores");

        return self::SUCCESS;
    }
}
