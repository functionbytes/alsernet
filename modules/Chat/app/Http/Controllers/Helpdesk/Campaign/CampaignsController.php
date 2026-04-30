<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Campaign;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Http\Requests\Campaigns\StoreCampaignRequest;
use Modules\Chat\Http\Requests\Campaigns\UpdateCampaignRequest;
use Modules\Chat\Models\Campaigns\Campaign;
use Modules\Chat\Models\Campaigns\CampaignTemplate;
use Modules\Chat\Services\Campaigns\CampaignService;

class CampaignsController extends Controller
{
    public function __construct(
        private readonly CampaignService $service
    ) {}

    /**
     * Display a listing of campaigns.
     *
     * GET /chats/chat/campaigns
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $campaigns = $this->service->paginatedCampaigns($request);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $campaigns]);
        }

        return view('Chat::settings.campaigns.index', compact('campaigns'));
    }

    /**
     * Display campaign templates library.
     *
     * GET /chats/chat/campaigns/templates
     */
    public function templates(Request $request): View|JsonResponse
    {
        $templates = $this->service->paginatedTemplates($request);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $templates]);
        }

        return view('Chat::settings.campaigns.templates', compact('templates'));
    }

    /**
     * Show the form for creating a new campaign.
     *
     * GET /chats/chat/campaigns/create
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Campaign::class);

        $template = $request->filled('template_id')
            ? CampaignTemplate::find($request->template_id)
            : null;

        return view('Chat::settings.campaigns.create', compact('template'));
    }

    /**
     * Store a newly created campaign in storage.
     *
     * POST /chats/chat/campaigns
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        try {
            $campaign = $this->service->create($request);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => $campaign,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified campaign.
     *
     * GET /chats/chat/campaigns/{campaign}
     */
    public function show(Request $request, string $campaign): View|JsonResponse
    {
        $campaignModel = Campaign::with('impressions')->findOrFail($campaign);
        $this->authorize('view', $campaignModel);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $campaignModel]);
        }

        return view('Chat::settings.campaigns.show', ['campaign' => $campaignModel]);
    }

    /**
     * Show the form for editing the specified campaign.
     *
     * GET /chats/chat/campaigns/{campaign}/edit
     */
    public function edit(string $campaign): View
    {
        $campaignModel = Campaign::findOrFail($campaign);
        $this->authorize('update', $campaignModel);

        return view('Chat::settings.campaigns.edit', ['campaign' => $campaignModel]);
    }

    /**
     * Update the specified campaign in storage.
     *
     * PUT /chats/chat/campaigns/{campaign}
     */
    public function update(UpdateCampaignRequest $request, string $campaign): JsonResponse
    {
        $campaignModel = Campaign::findOrFail($campaign);
        $this->authorize('update', $campaignModel);

        try {
            $updated = $this->service->update($campaignModel, $request);

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified campaign from storage.
     *
     * DELETE /chats/chat/campaigns/{campaign}
     */
    public function destroy(string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::findOrFail($campaign);
            $this->authorize('delete', $campaignModel);
            $campaignModel->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish the campaign.
     *
     * POST /chats/chat/campaigns/{campaign}/publish
     */
    public function publish(string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::findOrFail($campaign);
            $this->authorize('publish', $campaignModel);

            $updated = $this->service->publish($campaignModel);

            return response()->json([
                'success' => true,
                'message' => 'Campaign published successfully',
                'data' => $updated,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pause the campaign.
     *
     * POST /chats/chat/campaigns/{campaign}/pause
     */
    public function pause(string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::findOrFail($campaign);
            $this->authorize('pause', $campaignModel);

            $updated = $this->service->pause($campaignModel);

            return response()->json([
                'success' => true,
                'message' => 'Campaign paused successfully',
                'data' => $updated,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume the paused campaign.
     *
     * POST /chats/chat/campaigns/{campaign}/resume
     */
    public function resume(string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::findOrFail($campaign);
            $this->authorize('resume', $campaignModel);

            $updated = $this->service->resume($campaignModel);

            return response()->json([
                'success' => true,
                'message' => 'Campaign resumed successfully',
                'data' => $updated,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * End the campaign.
     *
     * POST /chats/chat/campaigns/{campaign}/end
     */
    public function end(string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::findOrFail($campaign);
            $this->authorize('end', $campaignModel);

            $updated = $this->service->end($campaignModel);

            return response()->json([
                'success' => true,
                'message' => 'Campaign ended successfully',
                'data' => $updated,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to end campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaign statistics.
     *
     * GET /chats/chat/campaigns/{campaign}/statistics
     */
    public function statistics(Request $request, string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::with('impressions')->findOrFail($campaign);
            $this->authorize('view', $campaignModel);

            return response()->json([
                'success' => true,
                'data' => $this->service->statistics($campaignModel),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve campaign statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate the campaign.
     *
     * POST /chats/chat/campaigns/{campaign}/duplicate
     */
    public function duplicate(string $campaign): JsonResponse
    {
        try {
            $original = Campaign::findOrFail($campaign);
            $this->authorize('duplicate', $original);

            $duplicate = $this->service->duplicate($original);

            return response()->json([
                'success' => true,
                'message' => 'Campaign duplicated successfully',
                'data' => $duplicate,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
