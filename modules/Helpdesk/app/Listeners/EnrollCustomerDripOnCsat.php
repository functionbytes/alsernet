<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\CsatRatingAnswered;
use Modules\Helpdesk\Jobs\ProcessDripStepJob;
use Modules\Helpdesk\Models\Campaigns\DripCampaign;
use Modules\Helpdesk\Models\Campaigns\DripExecution;

class EnrollCustomerDripOnCsat implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'drip';

    public int $tries = 2;

    public function handle(CsatRatingAnswered $event): void
    {
        $rating = $event->rating;

        if ($rating->rating > 2) {
            return;
        }

        $customer = $rating->customer;

        if (! $customer) {
            return;
        }

        $campaigns = DripCampaign::forTrigger('csat_low')->get();

        foreach ($campaigns as $campaign) {
            $execution = DripExecution::updateOrCreate(
                ['campaign_id' => $campaign->id, 'customer_id' => $customer->id],
                ['status' => 'active', 'current_step' => 0, 'started_at' => now()],
            );

            ProcessDripStepJob::dispatch($execution);
        }
    }

    public function failed(CsatRatingAnswered $event, \Throwable $exception): void
    {
        Log::error('EnrollCustomerDripOnCsat failed', [
            'rating_id' => $event->rating->id ?? null,
            'customer_id' => $event->rating->customer_id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
