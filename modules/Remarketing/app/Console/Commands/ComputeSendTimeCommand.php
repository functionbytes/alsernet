<?php

namespace Modules\Remarketing\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Remarketing\Models\Customer;

class ComputeSendTimeCommand extends Command
{
    protected $signature = 'remarketing:compute-send-time
                            {--store= : Only process a specific store ID}
                            {--min-opens=3 : Minimum opens required to compute a preference}';

    protected $description = 'Compute per-customer preferred send hour from open history';

    public function handle(): int
    {
        $minOpens = (int) $this->option('min-opens');
        $storeId = $this->option('store') ? (int) $this->option('store') : null;

        $query = DB::table('remarketing_messages')
            ->select('customer_id', DB::raw('HOUR(opened_at) as hour'), DB::raw('COUNT(*) as opens'))
            ->whereNotNull('opened_at')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id', DB::raw('HOUR(opened_at)'))
            ->having('opens', '>=', $minOpens);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // Get best hour per customer: the hour with most opens
        $bestHours = DB::table(DB::raw("({$query->toSql()}) as hourly"))
            ->mergeBindings($query)
            ->select('customer_id', DB::raw('hour'), DB::raw('MAX(opens) as max_opens'))
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        if ($bestHours->isEmpty()) {
            $this->info('No open data found to compute preferences.');

            return self::SUCCESS;
        }

        $updated = 0;

        $bestHours->chunk(500)->each(function ($chunk) use (&$updated): void {
            foreach ($chunk as $row) {
                Customer::query()
                    ->where('id', $row->customer_id)
                    ->update(['preferred_send_hour' => (int) $row->hour]);
                $updated++;
            }
        });

        $this->info("Updated preferred_send_hour for {$updated} customers.");

        return self::SUCCESS;
    }
}
