<?php

namespace Modules\HelpdeskCampaigns\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;

/**
 * Decides whether a given visitor should see a campaign right now,
 * based on per-campaign frequency capping rules:
 *  - max_impressions_per_user: hard ceiling on lifetime views per visitor
 *  - cooldown_minutes: minimum time between consecutive views to same visitor
 *
 * Visitor identity is resolved in this priority order:
 *  1. customer_id (logged-in customer)
 *  2. customer_session_id (anonymous session)
 *  3. ip_address (anonymous, no cookie)
 *
 * Hot-path optimization: frequency data is stored in Redis to avoid DB queries
 * on every tracking request. Cache is invalidated by RecordImpressionJob after
 * each impression is persisted.
 */
class FrequencyCapService
{
    public function shouldShow(Campaign $campaign, array $visitor): bool
    {
        if ($campaign->status !== 'active') {
            return false;
        }

        if (! $campaign->max_impressions_per_user && ! $campaign->cooldown_minutes) {
            return true;
        }

        $visitorKey = $this->visitorKey($visitor);

        if (! $visitorKey) {
            return true;
        }

        $cacheKey = "hc:cap:{$campaign->id}:{$visitorKey}";
        $ttl = max($campaign->cooldown_minutes ?? 0, 60) * 60;

        $data = Cache::store('redis')->remember($cacheKey, $ttl, fn () => $this->loadFromDb($campaign, $visitor));

        if ($campaign->max_impressions_per_user && $data['count'] >= $campaign->max_impressions_per_user) {
            return false;
        }

        if ($campaign->cooldown_minutes && $data['last']) {
            $lastSeen = Carbon::parse($data['last']);
            if (now()->diffInMinutes($lastSeen) < $campaign->cooldown_minutes) {
                return false;
            }
        }

        return true;
    }

    /**
     * Called by RecordImpressionJob after persisting — invalidates the cap cache
     * so the next request gets a fresh count from DB.
     */
    public function invalidate(int $campaignId, array $visitor): void
    {
        $visitorKey = $this->visitorKey($visitor);

        if ($visitorKey) {
            Cache::store('redis')->forget("hc:cap:{$campaignId}:{$visitorKey}");
        }
    }

    private function loadFromDb(Campaign $campaign, array $visitor): array
    {
        $query = CampaignImpression::query()->where('campaign_id', $campaign->id);
        $this->applyVisitorFilter($query, $visitor);

        $latest = $query->latest('viewed_at')->value('viewed_at');
        $count = $query->count();

        return ['count' => $count, 'last' => $latest];
    }

    private function visitorKey(array $visitor): ?string
    {
        if (! empty($visitor['customer_id'])) {
            return 'c'.$visitor['customer_id'];
        }

        if (! empty($visitor['customer_session_id'])) {
            return 's'.substr(md5($visitor['customer_session_id']), 0, 12);
        }

        if (! empty($visitor['ip_address'])) {
            return 'i'.substr(md5($visitor['ip_address']), 0, 12);
        }

        return null;
    }

    private function applyVisitorFilter($query, array $visitor): void
    {
        if (! empty($visitor['customer_id'])) {
            $query->where('customer_id', $visitor['customer_id']);
        } elseif (! empty($visitor['customer_session_id'])) {
            $query->where('customer_session_id', $visitor['customer_session_id']);
        } elseif (! empty($visitor['ip_address'])) {
            $query->where('ip_address', $visitor['ip_address']);
        }
    }
}
