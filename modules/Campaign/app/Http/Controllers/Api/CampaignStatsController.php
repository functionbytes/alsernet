<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Campaign\Models\Campaign;

class CampaignStatsController extends Controller
{
    public function stats(string $uid): JsonResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $sent = max(1, $campaign->sent_count);

        return response()->json([
            'campaign_uid' => $campaign->uid,
            'name' => $campaign->name,
            'status' => $campaign->status,
            'sent_count' => $campaign->sent_count,
            'open_count' => $campaign->open_count,
            'click_count' => $campaign->click_count,
            'bounce_count' => $campaign->bounce_count,
            'unsubscribe_count' => $campaign->unsubscribe_count,
            'feedback_count' => $campaign->feedback_count,
            'failed_count' => $campaign->failed_count,
            'open_rate' => round(($campaign->open_count / $sent) * 100, 2),
            'click_rate' => round(($campaign->click_count / $sent) * 100, 2),
            'bounce_rate' => round(($campaign->bounce_count / $sent) * 100, 2),
            'updated_at' => $campaign->updated_at,
        ]);
    }
}
