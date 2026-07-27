<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\AutomationRun;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Services\AutomationService;

class CalculateRfmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public int $backoff = 0;

    public function __construct()
    {
        $this->onQueue('remarketing');
    }

    public function handle(AutomationService $automationService): void
    {
        Store::query()->where('status', 'active')->cursor()->each(
            fn (Store $store) => $this->processStore($store, $automationService)
        );
    }

    private function processStore(Store $store, AutomationService $automationService): void
    {
        $customers = Customer::query()
            ->where('store_id', $store->id)
            ->where('orders_count', '>', 0)
            ->get(['id', 'last_order_at', 'orders_count', 'clv_historical', 'rfm_frequency']);

        if ($customers->isEmpty()) {
            return;
        }

        $recencyValues = $customers->map(fn ($c) => $c->last_order_at ? $c->last_order_at->diffInDays(now()) : PHP_INT_MAX);
        $frequencyValues = $customers->pluck('orders_count');
        $monetaryValues = $customers->pluck('clv_historical');

        // Quintile scoring requires the full dataset to compute percentiles,
        // so the initial ->get() is intentional. We only chunk the writes.
        // Sort each array once here (not per-customer inside quintileScore),
        // since sort() is O(n log n) and was previously called once per customer.
        $recencyArray = $recencyValues->toArray();
        $frequencyArray = $frequencyValues->toArray();
        $monetaryArray = $monetaryValues->toArray();

        sort($recencyArray);
        sort($frequencyArray);
        sort($monetaryArray);

        foreach ($customers->chunk(1000) as $chunk) {
            foreach ($chunk as $customer) {
                $recencyDays = $customer->last_order_at
                    ? $customer->last_order_at->diffInDays(now())
                    : PHP_INT_MAX;

                // Lower recency days = higher score (more recent is better)
                $rScore = $this->quintileScore($recencyDays, $recencyArray, true);
                $fScore = $this->quintileScore($customer->orders_count, $frequencyArray);
                $mScore = $this->quintileScore((float) $customer->clv_historical, $monetaryArray);

                Customer::query()->where('id', $customer->id)->update([
                    'rfm_recency' => $rScore,
                    'rfm_frequency' => $fScore,
                    'rfm_monetary' => $mScore,
                ]);
            }
        }

        $this->triggerWinBackAutomations($store, $automationService);
    }

    private function quintileScore(mixed $value, array $sortedAll, bool $invert = false): int
    {
        $count = count($sortedAll);

        if ($count === 0) {
            return 3;
        }

        $rank = array_search($value, $sortedAll);

        if ($rank === false) {
            $rank = 0;
        }

        $percentile = ($rank / $count) * 100;

        $score = match (true) {
            $percentile <= 20 => 1,
            $percentile <= 40 => 2,
            $percentile <= 60 => 3,
            $percentile <= 80 => 4,
            default => 5,
        };

        return $invert ? (6 - $score) : $score;
    }

    private function triggerWinBackAutomations(Store $store, AutomationService $automationService): void
    {
        $automations = Automation::query()
            ->where('store_id', $store->id)
            ->where('trigger', 'win_back')
            ->where('status', 'active')
            ->get();

        if ($automations->isEmpty()) {
            return;
        }

        $winBackCandidates = Customer::query()
            ->where('store_id', $store->id)
            ->where('last_order_at', '<', now()->subDays(90))
            ->where('rfm_frequency', '>=', 2)
            ->cursor();

        foreach ($winBackCandidates as $customer) {
            foreach ($automations as $automation) {
                $hasActiveRun = AutomationRun::query()
                    ->where('automation_id', $automation->id)
                    ->where('customer_id', $customer->id)
                    ->where('status', 'running')
                    ->exists();

                if (! $hasActiveRun) {
                    $automationService->trigger($automation, $customer, []);
                }
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateRfmJob failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
