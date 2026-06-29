<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\Customer;

/**
 * Detect customers who haven't ordered in 90+ days and trigger a win-back campaign.
 * Schedule: daily.
 */
class DetectWinBackJob implements ShouldQueue
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
        $cutoff = now()->subDays(90);
        $recentTriggerCutoff = now()->subDays(90);
        $sent = 0;

        Customer::query()
            ->where('orders_count', '>', 0)
            ->where('last_order_at', '<', $cutoff)
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(500, function ($customers) use ($recentTriggerCutoff, &$sent) {
                $ids = $customers->pluck('id')->all();

                $alreadyTriggered = DB::table('remarketing_automation_triggers_log')
                    ->where('trigger_type', 'win_back')
                    ->where('triggered_at', '>=', $recentTriggerCutoff)
                    ->whereIn('customer_id', $ids)
                    ->pluck('customer_id')
                    ->mapWithKeys(fn ($id) => [(int) $id => true])
                    ->all();

                foreach ($customers as $customer) {
                    if (isset($alreadyTriggered[(int) $customer->id])) {
                        continue;
                    }
                    SendWinBackMailJob::dispatch((int) $customer->id);
                    $sent++;
                }
            });

        Log::info('DetectWinBackJob completed', ['sent' => $sent]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('DetectWinBackJob failed', ['error' => $e->getMessage()]);
    }
}
