<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Services\OCI8Service;
use Modules\Erp\Support\CustomerOrdersQuery;

class CacheCustomerOrders extends Command
{
    protected $signature = 'erp:cache-customer-orders
                            {id : Customer ID}
                            {--status= : Filter by order status}
                            {--from= : Filter from date (YYYY-MM-DD)}
                            {--to= : Filter to date (YYYY-MM-DD)}
                            {--offset=0 : Pagination offset}
                            {--limit=10 : Pagination limit}';

    protected $description = 'Fetch and cache orders for a customer from PEDIDOCLI_CENTRAL (runs slow full-scan query in background)';

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $limit = min((int) ($this->option('limit') ?: 10), 100);
        $offset = (int) ($this->option('offset') ?: 0);
        $status = (string) ($this->option('status') ?: '');
        $from = (string) ($this->option('from') ?: '');
        $to = (string) ($this->option('to') ?: '');

        $cacheKey = CustomerOrdersQuery::cacheKey($id, $status, $from, $to, $offset, $limit);

        if (cache()->has($cacheKey)) {
            return self::SUCCESS;
        }

        [$sql, $bindings] = CustomerOrdersQuery::build($id, $status, $from, $to, $offset, $limit);

        try {
            $rows = app(OCI8Service::class)->query($sql, $bindings);
            cache()->put($cacheKey, $rows, now()->addHour());
            Log::info("erp:cache-customer-orders: cached customer {$id}, ".count($rows).' rows');
        } catch (\Exception $e) {
            Log::error("erp:cache-customer-orders: failed for customer {$id}", ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
