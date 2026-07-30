<?php

namespace Modules\HelpdeskCampaigns\Services;

use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignVariant;

/**
 * Picks one variant of a campaign using weighted random selection.
 * Visitor stickiness: same visitor (by customer_id, session_id, or IP) always
 * gets the same variant within the campaign by hashing the identity into a
 * deterministic bucket. This keeps A/B test results clean.
 */
class VariantSelector
{
    public function pick(Campaign $campaign, array $visitor): ?CampaignVariant
    {
        $variants = $campaign->variants()->orderBy('id')->get();

        if ($variants->isEmpty()) {
            return null;
        }

        if ($variants->count() === 1) {
            return $variants->first();
        }

        $totalWeight = $variants->sum('weight') ?: 100;
        $bucket = $this->visitorBucket($campaign, $visitor, $totalWeight);

        $cursor = 0;
        foreach ($variants as $variant) {
            $cursor += (int) $variant->weight;
            if ($bucket < $cursor) {
                return $variant;
            }
        }

        return $variants->last();
    }

    private function visitorBucket(Campaign $campaign, array $visitor, int $totalWeight): int
    {
        $identity = $visitor['customer_id']
            ?? $visitor['customer_session_id']
            ?? $visitor['ip_address']
            ?? bin2hex(random_bytes(4));

        $hash = crc32("{$campaign->id}:{$identity}");

        return abs($hash) % $totalWeight;
    }
}
