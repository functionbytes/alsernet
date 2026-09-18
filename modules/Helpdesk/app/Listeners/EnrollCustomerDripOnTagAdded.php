<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationTagAdded;
use Modules\Helpdesk\Jobs\ProcessDripStepJob;
use Modules\Helpdesk\Models\Campaigns\DripCampaign;
use Modules\Helpdesk\Models\Campaigns\DripExecution;

class EnrollCustomerDripOnTagAdded implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'drip';

    public int $tries = 2;

    public function handle(ConversationTagAdded $event): void
    {
        $customer = $event->conversation->customer;

        if (! $customer) {
            return;
        }

        $campaigns = DripCampaign::forTrigger('tag_added', $event->tag->slug)->get();

        foreach ($campaigns as $campaign) {
            $execution = DripExecution::updateOrCreate(
                ['campaign_id' => $campaign->id, 'customer_id' => $customer->id],
                ['status' => 'active', 'current_step' => 0, 'started_at' => now()],
            );

            if ($execution->wasRecentlyCreated) {
                ProcessDripStepJob::dispatch($execution);
            }
        }
    }

    public function failed(ConversationTagAdded $event, \Throwable $exception): void
    {
        Log::error('EnrollCustomerDripOnTagAdded failed', [
            'conversation_id' => $event->conversation->id ?? null,
            'tag_slug' => $event->tag->slug ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
