<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Jobs\ProcessDripStepJob;
use Modules\Helpdesk\Models\Campaigns\DripCampaign;
use Modules\Helpdesk\Models\Campaigns\DripExecution;

class EnrollCustomerToDripCampaigns implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'drip';

    public int $tries = 2;

    public function handle(ConversationClosed $event): void
    {
        $customer = $event->conversation->customer;

        if (! $customer) {
            return;
        }

        $campaigns = DripCampaign::forTrigger('conversation_closed')->get();

        foreach ($campaigns as $campaign) {
            $execution = DripExecution::updateOrCreate(
                ['campaign_id' => $campaign->id, 'customer_id' => $customer->id],
                ['status' => 'active', 'current_step' => 0, 'started_at' => now()],
            );

            ProcessDripStepJob::dispatch($execution);
        }
    }

    public function failed(ConversationClosed $event, \Throwable $exception): void
    {
        Log::error('EnrollCustomerToDripCampaigns failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'customer_id' => $event->conversation->customer_id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
