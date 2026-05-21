<?php

namespace Modules\Remarketing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\Remarketing\Jobs\SendVipRewardMailJob;
use Modules\Remarketing\Models\Customer;

class CheckVipMilestoneListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'remarketing';

    public int $tries = 3;

    public int $backoff = 10;

    /** Lifetime spend thresholds in EUR that trigger a VIP reward. */
    private const MILESTONES = [500, 1000, 2500, 5000, 10000];

    public function handle(PsOrderCreated $event): void
    {
        $email = $event->email();
        $orderTotal = $event->total();

        if (! $email || $orderTotal === null) {
            return;
        }

        $customer = Customer::query()->where('email', $email)->first();

        if (! $customer) {
            return;
        }

        // clv_historical is updated by the sync jobs after order creation;
        // at listener time it still holds the previous lifetime value.
        $previousLifetime = (float) ($customer->clv_historical ?? 0);
        $newLifetime = $previousLifetime + $orderTotal;

        foreach (self::MILESTONES as $threshold) {
            if ($previousLifetime < $threshold && $newLifetime >= $threshold) {
                SendVipRewardMailJob::dispatch((int) $customer->id, $threshold, $newLifetime);
                break;
            }
        }
    }

    public function failed(PsOrderCreated $event, \Throwable $exception): void
    {
        Log::error('CheckVipMilestoneListener failed', [
            'customer_email' => $event->email(),
            'error' => $exception->getMessage(),
        ]);
    }
}
