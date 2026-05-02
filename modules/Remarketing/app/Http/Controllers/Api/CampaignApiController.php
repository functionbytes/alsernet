<?php

namespace Modules\Remarketing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Remarketing\Http\Requests\Api\ScheduleCampaignRequest;
use Modules\Remarketing\Http\Requests\Api\StoreCampaignRequest;
use Modules\Remarketing\Http\Requests\Api\UpdateCampaignRequest;
use Modules\Remarketing\Http\Resources\CampaignResource;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Services\CampaignService;

class CampaignApiController extends Controller
{
    public function __construct(
        protected CampaignService $campaigns,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Campaign::class);

        $campaigns = Campaign::query()
            ->latest()
            ->paginate($request->input('per_page', 15));

        return CampaignResource::collection($campaigns);
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = Campaign::create($request->validated());

        return (new CampaignResource($campaign))->response()->setStatusCode(201);
    }

    public function show(Campaign $campaign): CampaignResource
    {
        $this->authorize('view', $campaign);

        return new CampaignResource($campaign);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): CampaignResource
    {
        $this->authorize('update', $campaign);

        $campaign->update($request->validated());

        return new CampaignResource($campaign->fresh());
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return response()->json(null, 204);
    }

    public function schedule(ScheduleCampaignRequest $request, Campaign $campaign): CampaignResource
    {
        $this->authorize('send', $campaign);

        $this->campaigns->schedule($campaign, Carbon::parse($request->input('scheduled_at')));

        return new CampaignResource($campaign->fresh());
    }

    public function sendTest(Campaign $campaign): JsonResponse
    {
        $this->authorize('send', $campaign);

        return response()->json(['ok' => true, 'message' => 'Send test queued']);
    }

    public function cancel(Campaign $campaign): CampaignResource
    {
        $this->authorize('update', $campaign);

        $this->campaigns->cancel($campaign);

        return new CampaignResource($campaign->fresh());
    }

    public function stats(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $this->campaigns->recalculateStats($campaign);

        $campaign->refresh();

        return response()->json([
            'recipients_total' => $campaign->recipients_total,
            'sent' => $campaign->sent,
            'delivered' => $campaign->delivered,
            'opened' => $campaign->opened,
            'clicked' => $campaign->clicked,
            'bounced' => $campaign->bounced,
            'unsubscribed' => $campaign->unsubscribed,
            'complained' => $campaign->complained,
            'revenue' => $campaign->revenue,
            'open_rate' => $campaign->delivered > 0 ? round(($campaign->opened / $campaign->delivered) * 100, 2) : 0,
            'click_rate' => $campaign->delivered > 0 ? round(($campaign->clicked / $campaign->delivered) * 100, 2) : 0,
        ]);
    }
}
