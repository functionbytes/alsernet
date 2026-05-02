<?php

namespace Modules\Remarketing\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Remarketing\Jobs\SendEmailJob;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\ConsentEvent;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Message;

class CampaignService
{
    public function __construct(
        private readonly ConsentService $consentService,
        private readonly SegmentService $segmentService,
    ) {}

    /**
     * Schedule a campaign for sending at a specific time.
     */
    public function schedule(Campaign $campaign, Carbon $scheduledAt): void
    {
        $campaign->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    /**
     * Begin sending a campaign to all eligible segment members.
     * Returns the number of messages dispatched.
     */
    public function send(Campaign $campaign): int
    {
        $campaign->update([
            'status' => 'sending',
            'started_at' => now(),
        ]);

        $store = $campaign->store;
        $dispatched = 0;

        $query = $campaign->segment_id
            ? $this->segmentService->getMembers($campaign->segment)
            : Customer::query()
                ->where('store_id', $campaign->store_id)
                ->withEmailMarketingConsent();

        $query->chunk(500, function ($customers) use ($campaign, $store, &$dispatched): void {
            foreach ($customers as $customer) {
                if ($this->consentService->isSuppressed($store, $customer->email)) {
                    continue;
                }

                $message = Message::create([
                    'store_id' => $campaign->store_id,
                    'customer_id' => $customer->id,
                    'campaign_id' => $campaign->id,
                    'email' => $customer->email,
                    'subject' => $campaign->subject,
                    'status' => 'queued',
                    'open_token' => Str::random(64),
                    'click_token' => Str::random(64),
                ]);

                SendEmailJob::dispatch($message)->onQueue('remarketing');

                $dispatched++;
            }
        });

        $campaign->update(['recipients_total' => $dispatched]);

        return $dispatched;
    }

    /**
     * Cancel a campaign that hasn't finished sending.
     */
    public function cancel(Campaign $campaign): void
    {
        $campaign->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    /**
     * Recalculate campaign stats from the messages table.
     */
    public function recalculateStats(Campaign $campaign): void
    {
        $stats = Message::query()
            ->where('campaign_id', $campaign->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(status = "sent") as sent,
                SUM(status = "delivered") as delivered,
                SUM(status = "bounced") as bounced,
                SUM(status IN ("opened", "clicked")) as opened,
                SUM(status = "clicked") as clicked,
                SUM(status = "complained") as complained,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as unique_opens,
                SUM(revenue) as revenue
            ')
            ->first();

        // Count unsubscribes separately from consent events
        $unsubscribed = ConsentEvent::query()
            ->where('store_id', $campaign->store_id)
            ->where('event_type', 'withdrawn')
            ->whereIn('customer_id', function ($sub) use ($campaign): void {
                $sub->select('customer_id')
                    ->from('remarketing_messages')
                    ->where('campaign_id', $campaign->id);
            })
            ->count();

        $campaign->update([
            'sent' => (int) ($stats->sent ?? 0),
            'delivered' => (int) ($stats->delivered ?? 0),
            'bounced' => (int) ($stats->bounced ?? 0),
            'opened' => (int) ($stats->opened ?? 0),
            'clicked' => (int) ($stats->clicked ?? 0),
            'unsubscribed' => $unsubscribed,
            'complained' => (int) ($stats->complained ?? 0),
            'revenue' => (float) ($stats->revenue ?? 0),
        ]);
    }
}
