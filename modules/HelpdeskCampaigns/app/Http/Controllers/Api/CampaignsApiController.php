<?php

namespace Modules\HelpdeskCampaigns\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Events\CampaignPaused;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Events\CampaignResumed;
use Modules\HelpdeskCampaigns\Http\Requests\Api\StoreCampaignApiRequest;
use Modules\HelpdeskCampaigns\Http\Requests\Api\UpdateCampaignApiRequest;
use Modules\HelpdeskCampaigns\Http\Resources\CampaignResource;
use Modules\HelpdeskCampaigns\Models\Campaign;

/**
 * REST API for HelpdeskCampaigns. Authenticated via Sanctum.
 * Routes registered in modules/HelpdeskCampaigns/routes/api.php under
 * `api/v1/helpdesk/campaigns`.
 */
class CampaignsApiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Campaign::class);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $campaigns = Campaign::query()
            ->withCount([
                'impressions',
                'impressions as clicks_count' => fn ($q) => $q->whereNotNull('clicked_at'),
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return CampaignResource::collection($campaigns);
    }

    public function show(Campaign $campaign): CampaignResource
    {
        $this->authorize('view', $campaign);

        $campaign->loadCount([
            'impressions',
            'impressions as clicks_count' => fn ($q) => $q->whereNotNull('clicked_at'),
        ]);

        return new CampaignResource($campaign);
    }

    public function store(StoreCampaignApiRequest $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $campaign = Campaign::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_created'),
            'data' => new CampaignResource($campaign),
        ], 201);
    }

    public function update(UpdateCampaignApiRequest $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $campaign->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_updated'),
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_deleted'),
        ]);
    }

    public function publish(Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $campaign->update([
            'status' => 'active',
            'published_at' => $campaign->published_at ?? now(),
        ]);

        CampaignPublished::dispatch($campaign);

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_activated'),
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }

    public function pause(Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $campaign->update(['status' => 'paused']);
        CampaignPaused::dispatch($campaign);

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_paused'),
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }

    public function resume(Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $campaign->update(['status' => 'active']);
        CampaignResumed::dispatch($campaign);

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_activated'),
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }

    public function end(Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $campaign->update([
            'status' => 'ended',
            'ends_at' => now(),
        ]);

        CampaignEnded::dispatch($campaign);

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_sent'),
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }
}
