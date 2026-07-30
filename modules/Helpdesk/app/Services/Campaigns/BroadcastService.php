<?php

namespace Modules\Helpdesk\Services\Campaigns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Jobs\Campaigns\SendBroadcastMessageJob;
use Modules\Helpdesk\Models\Campaigns\Broadcast;
use Modules\Helpdesk\Models\Campaigns\BroadcastRecipient;
use Modules\Helpdesk\Models\Customer;

class BroadcastService
{
    /**
     * Preview how many customers match the given segment filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function previewSegment(array $filters): int
    {
        return $this->buildCustomerQuery($filters)->count();
    }

    /**
     * Dispatch a broadcast: build recipients and enqueue one job per customer.
     * Jobs are throttled to a max of 5/second to respect Meta rate limits.
     */
    public function dispatchBroadcast(Broadcast $broadcast): void
    {
        $broadcast->markAsSending();

        $filters = $broadcast->filters ?? [];
        $customers = $this->buildCustomerQuery($filters)->get(['id']);

        if ($customers->isEmpty()) {
            Log::warning('Broadcast has no matching customers', ['broadcast_id' => $broadcast->id]);
            $broadcast->markAsFailed();

            return;
        }

        DB::transaction(function () use ($broadcast, $customers): void {
            foreach ($customers as $customer) {
                BroadcastRecipient::query()->create([
                    'broadcast_id' => $broadcast->id,
                    'customer_id' => $customer->id,
                    'status' => 'pending',
                ]);
            }

            $broadcast->update(['recipients_count' => $customers->count()]);
        });

        // Dispatch jobs with delay to throttle: max 5/second for Meta API compliance
        $recipients = BroadcastRecipient::query()
            ->where('broadcast_id', $broadcast->id)
            ->where('status', 'pending')
            ->get(['id']);

        foreach ($recipients as $index => $recipient) {
            $delaySeconds = (int) floor($index / 5); // batch of 5 per second

            SendBroadcastMessageJob::dispatch($broadcast->id, $recipient->id)
                ->onQueue('helpdesk-broadcasts')
                ->delay(now()->addSeconds($delaySeconds));
        }
    }

    /**
     * Build a query for customers matching the given segment filters.
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildCustomerQuery(array $filters)
    {
        $query = Customer::query()->whereNull('deleted_at');

        // Filter by channel availability
        if ($channel = $filters['channel'] ?? null) {
            $query->when($channel === 'whatsapp', fn ($q) => $q->whereNotNull('whatsapp_phone'))
                ->when($channel === 'facebook', fn ($q) => $q->whereNotNull('facebook_psid'))
                ->when($channel === 'instagram', fn ($q) => $q->whereNotNull('instagram_id'))
                ->when($channel === 'email', fn ($q) => $q->whereNotNull('email'));
        }

        // Filter by tag (customers who had a conversation tagged with this)
        if ($tag = $filters['tag'] ?? null) {
            $query->whereHas('conversations', function ($q) use ($tag) {
                $q->whereHas('conversationTags', function ($q2) use ($tag) {
                    $q2->where('slug', $tag);
                });
            });
        }

        // Filter by last contact after a given date
        if ($lastContactAfter = $filters['last_contact_after'] ?? null) {
            $query->where('last_seen_at', '>=', $lastContactAfter);
        }

        // Filter by last contact before a given date
        if ($lastContactBefore = $filters['last_contact_before'] ?? null) {
            $query->where('last_seen_at', '<=', $lastContactBefore);
        }

        // Exclude banned customers
        $query->whereNull('banned_at');

        return $query;
    }
}
