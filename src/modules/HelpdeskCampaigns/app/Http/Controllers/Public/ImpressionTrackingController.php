<?php

namespace Modules\HelpdeskCampaigns\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\HelpdeskCampaigns\Events\CampaignImpressionRecorded;
use Modules\HelpdeskCampaigns\Http\Requests\Public\RecordImpressionRequest;
use Modules\HelpdeskCampaigns\Jobs\RecordImpressionJob;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;
use Modules\HelpdeskCampaigns\Services\FrequencyCapService;
use Modules\HelpdeskCampaigns\Services\TargetingService;
use Modules\HelpdeskCampaigns\Services\VariantSelector;

/**
 * Public, unauthenticated tracking endpoints for campaign impressions and
 * clicks. Called from the JS snippet embedded on customer-facing pages.
 *
 * Impressions are queued via RecordImpressionJob so the HTTP response stays
 * <50ms even under heavy traffic. Clicks are recorded synchronously because
 * the customer is about to navigate away and we cannot risk losing the data
 * mid-queue.
 */
class ImpressionTrackingController extends Controller
{
    /**
     * POST /helpdesk/campaigns/track/view — fire-and-forget impression.
     */
    public function recordView(RecordImpressionRequest $request, FrequencyCapService $cap, TargetingService $targeting, VariantSelector $variants): JsonResponse
    {
        $campaign = Campaign::query()
            ->where('id', $request->integer('campaign_id'))
            ->where('status', 'active')
            ->first();

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not active or not found.',
            ], 404);
        }

        $visitor = [
            'customer_id' => $request->input('customer_id'),
            'customer_session_id' => $request->input('customer_session_id'),
            'ip_address' => $request->ip(),
            'page_url' => $request->input('page_url'),
            'device_type' => $request->input('device_type'),
            'country' => $request->input('country'),
            'language' => $request->input('language'),
        ];

        if (! $targeting->matches($campaign, $visitor)) {
            return response()->json([
                'success' => false,
                'message' => 'Visitor does not match campaign targeting.',
                'code' => 'TARGETING_MISMATCH',
            ], 200);
        }

        if (! $cap->shouldShow($campaign, $visitor)) {
            return response()->json([
                'success' => false,
                'message' => 'Frequency cap reached for this visitor.',
                'code' => 'FREQUENCY_CAPPED',
            ], 200);
        }

        // Deduplication: prevent the same visitor from recording multiple impressions
        // for the same campaign within a short window (e.g. page reload, double-fire).
        // TTL is 30 s — short enough not to block legitimate revisits but long enough
        // to absorb rapid-fire requests before the async job persists the row.
        $dedupKey = $this->dedupKey($campaign->id, $visitor);
        if ($dedupKey && Cache::has($dedupKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Impression already recorded recently.',
                'code' => 'DUPLICATE_IMPRESSION',
            ]);
        }

        if ($dedupKey) {
            Cache::put($dedupKey, 1, 30);
        }

        $variant = $variants->pick($campaign, $visitor);
        $impressionId = (string) Str::uuid();

        RecordImpressionJob::dispatch($campaign->id, [
            'customer_id' => $visitor['customer_id'],
            'customer_session_id' => $visitor['customer_session_id'],
            'variant_id' => $variant?->id,
            'impression_id' => $impressionId,
            'page_url' => $request->input('page_url'),
            'device_type' => $request->input('device_type'),
            'browser' => $request->input('browser'),
            'ip_address' => $visitor['ip_address'],
            'country' => $request->input('country'),
            'metadata' => $request->input('metadata', []),
            'viewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.impression_tracked'),
            'impression_id' => $impressionId,
            'variant' => $variant ? [
                'id' => $variant->id,
                'label' => $variant->label,
                'content' => $variant->content,
                'appearance' => $variant->appearance,
            ] : null,
        ]);
    }

    /**
     * POST /helpdesk/campaigns/track/click/{impressionUuid}
     * Synchronous so we don't lose the click if the user navigates away.
     * Accepts the UUID (impression_id), not the numeric primary key, so the
     * widget JS does not need to know any internal IDs.
     */
    public function recordClick(string $impressionUuid): JsonResponse
    {
        $impression = CampaignImpression::where('impression_id', $impressionUuid)->first();

        if (! $impression) {
            return response()->json([
                'success' => false,
                'message' => 'Impression not found.',
            ], 404);
        }

        // Atomic update — prevents duplicate CampaignImpressionRecorded events
        // if two requests arrive simultaneously before either is committed.
        $updated = CampaignImpression::where('impression_id', $impressionUuid)
            ->whereNull('clicked_at')
            ->update(['clicked_at' => now()]);

        if (! $updated) {
            return response()->json([
                'success' => true,
                'message' => 'Click already recorded.',
            ]);
        }

        $impression->refresh();

        CampaignImpressionRecorded::dispatch($impression, wasClick: true);

        return response()->json([
            'success' => true,
            'message' => 'Click recorded.',
        ]);
    }

    private function dedupKey(int $campaignId, array $visitor): ?string
    {
        if (! empty($visitor['customer_session_id'])) {
            return 'hc:dedup:'.$campaignId.':s'.substr(md5($visitor['customer_session_id']), 0, 12);
        }

        if (! empty($visitor['customer_id'])) {
            return 'hc:dedup:'.$campaignId.':c'.$visitor['customer_id'];
        }

        if (! empty($visitor['ip_address'])) {
            return 'hc:dedup:'.$campaignId.':i'.substr(md5($visitor['ip_address']), 0, 12);
        }

        return null;
    }
}
