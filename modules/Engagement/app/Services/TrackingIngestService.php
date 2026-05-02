<?php

namespace Modules\Engagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Engagement\Jobs\ProcessEventBatchJob;
use Modules\Engagement\Models\VisitorSession;
use Modules\Helpdesk\Models\Inbox;

class TrackingIngestService
{
    public function ingest(array $events, string $sessionToken, Inbox $inbox): int
    {
        $accepted = 0;
        $rows = [];
        $now = now();
        $cutoff = now()->subHours(24);

        foreach ($events as $event) {
            $ts = Carbon::parse($event['ts']);

            if ($ts->lt($cutoff)) {
                continue;
            }

            $properties = $event['properties'] ?? [];
            $platform = $properties['platform'] ?? null;
            if (! in_array($platform, ['prestashop', 'shopify', 'woocommerce', 'custom'], true)) {
                $platform = null;
            }

            $rows[] = [
                'session_token' => $sessionToken,
                'customer_id' => null,
                'inbox_id' => $inbox->id,
                'event_name' => $event['name'],
                'platform' => $platform,
                'properties' => json_encode($properties),
                'occurred_at' => $ts,
                'received_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $accepted++;
        }

        if (! empty($rows)) {
            DB::connection('helpdesk')
                ->table('engagement_events')
                ->insert($rows);
        }

        // Refresh visitor session activity (current_url + last_activity_at)
        // so live-visitors view can show the visitor as active.
        $latestEvent = end($events) ?: null;
        $latestUrl = $latestEvent['properties']['url'] ?? null;
        VisitorSession::query()
            ->where('session_token', $sessionToken)
            ->update(array_filter([
                'last_activity_at' => $now,
                'current_url' => $latestUrl,
                'updated_at' => $now,
            ]));

        $this->linkCustomerIdAsync($sessionToken);

        ProcessEventBatchJob::dispatch($sessionToken, $inbox->id)
            ->onQueue('helpdesklivechat');

        return $accepted;
    }

    private function linkCustomerIdAsync(string $sessionToken): void
    {
        $session = VisitorSession::query()
            ->where('session_token', $sessionToken)
            ->first();

        if (! $session?->customer_id) {
            return;
        }

        DB::connection('helpdesk')
            ->table('engagement_events')
            ->where('session_token', $sessionToken)
            ->whereNull('customer_id')
            ->update(['customer_id' => $session->customer_id]);
    }
}
