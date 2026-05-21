<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Detect customers who bought consumable products N days ago and may need replenishment.
 * Schedule: daily.
 */
class DetectReplenishmentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue('remarketing');
    }

    public function handle(): void
    {
        if (! Schema::hasTable('remarketing_consumable_definitions')) {
            return;
        }

        $today = now()->startOfDay();
        $stats = ['definitions' => 0, 'candidates' => 0];

        DB::table('remarketing_consumable_definitions')
            ->orderBy('id')
            ->chunkById(100, function ($definitions) use ($today, &$stats) {
                foreach ($definitions as $def) {
                    $stats['definitions']++;
                    $targetDate = $today->copy()->subDays((int) $def->reorder_days);

                    $itemsTable = Schema::hasTable('remarketing_order_items')
                        ? 'remarketing_order_items'
                        : (Schema::hasTable('remarketing_order_lines') ? 'remarketing_order_lines' : null);

                    if (! $itemsTable) {
                        continue;
                    }

                    $candidates = DB::table('remarketing_orders as o')
                        ->join("{$itemsTable} as oi", 'oi.order_id', '=', 'o.id')
                        ->where('o.store_id', $def->store_id)
                        ->where('oi.external_product_id', $def->external_product_id)
                        ->whereDate('o.placed_at', $targetDate->toDateString())
                        ->whereNotNull('o.customer_id')
                        ->select('o.customer_id', 'oi.external_product_id')
                        ->distinct()
                        ->get();

                    foreach ($candidates as $row) {
                        $alreadySent = DB::table('remarketing_automation_triggers_log')
                            ->where('customer_id', $row->customer_id)
                            ->where('trigger_type', 'replenishment')
                            ->where('triggered_at', '>=', now()->subDays(7))
                            ->exists();

                        if (! $alreadySent) {
                            SendReplenishmentMailJob::dispatch(
                                (int) $row->customer_id,
                                (int) $row->external_product_id,
                            );
                            $stats['candidates']++;
                        }
                    }
                }
            });

        Log::info('DetectReplenishmentJob completed', $stats);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('DetectReplenishmentJob failed', ['error' => $e->getMessage()]);
    }
}
