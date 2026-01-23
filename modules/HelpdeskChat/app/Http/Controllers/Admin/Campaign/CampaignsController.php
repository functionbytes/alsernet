<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Campaign;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Modules\HelpdeskChat\Models\Campaigns\Campaign;
use Modules\HelpdeskChat\Models\Campaigns\CampaignTemplate;

class CampaignsController extends Controller
{
    /**
     * Display a listing of campaigns.
     *
     * GET /admin/helpdesk/campaigns
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Campaign::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $campaigns = $query->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $campaigns,
            ]);
        }

        return view('helpdeskChat::admin.campaigns.index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Display campaign templates library.
     *
     * GET /admin/helpdesk/campaigns/templates
     */
    public function templates(Request $request): View|JsonResponse
    {
        $query = CampaignTemplate::query();

        // Filter by category
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $templates = $query->orderBy('name')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $templates,
            ]);
        }

        return view('helpdeskChat::admin.campaigns.templates', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for creating a new campaign.
     *
     * GET /admin/helpdesk/campaigns/create
     */
    public function create(Request $request): View
    {
        $templateId = $request->get('template_id');
        $template = null;

        if ($templateId) {
            $template = CampaignTemplate::find($templateId);
        }

        return view('helpdeskChat::admin.campaigns.create', [
            'template' => $template,
        ]);
    }

    /**
     * Store a newly created campaign in storage.
     *
     * POST /admin/helpdesk/campaigns
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:popup,banner,slide-in,full-screen',
            'status' => 'nullable|in:draft,scheduled,active,ended,paused',
            'content' => 'nullable|array',
            'appearance' => 'nullable|array',
            'conditions' => 'nullable|array',
            'published_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:published_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $campaign = Campaign::create([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'status' => $request->status ?? 'draft',
                'content' => $request->content,
                'appearance' => $request->appearance,
                'conditions' => $request->conditions,
                'published_at' => $request->published_at,
                'ends_at' => $request->ends_at,
            ]);

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
     * GET /admin/helpdesk/campaigns/{campaign}
     */
    public function show(Request $request, string $campaign): View|JsonResponse
    {
        $campaignModel = Campaign::with('impressions')->findOrFail($campaign);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $campaignModel,
            ]);
        }

        return view('helpdeskChat::admin.campaigns.show', [
            'campaign' => $campaignModel,
        ]);
    }

    /**
     * Show the form for editing the specified campaign.
     *
     * GET /admin/helpdesk/campaigns/{campaign}/edit
     */
    public function edit(string $campaign): View
    {
        $campaignModel = Campaign::findOrFail($campaign);

        return view('helpdeskChat::admin.campaigns.edit', [
            'campaign' => $campaignModel,
        ]);
    }

    /**
     * Update the specified campaign in storage.
     *
     * PUT /admin/helpdesk/campaigns/{campaign}
     */
    public function update(Request $request, string $campaign): JsonResponse
    {
        $campaignModel = Campaign::findOrFail($campaign);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:popup,banner,slide-in,full-screen',
            'status' => 'nullable|in:draft,scheduled,active,ended,paused',
            'content' => 'nullable|array',
            'appearance' => 'nullable|array',
            'conditions' => 'nullable|array',
            'published_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:published_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $campaignModel->update([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'status' => $request->status ?? $campaignModel->status,
                'content' => $request->content,
                'appearance' => $request->appearance,
                'conditions' => $request->conditions,
                'published_at' => $request->published_at,
                'ends_at' => $request->ends_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data' => $campaignModel->fresh(),
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
     * DELETE /admin/helpdesk/campaigns/{campaign}
     */
    public function destroy(string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::findOrFail($campaign);
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
     * POST /admin/helpdesk/campaigns/{campaign}/publish
     */
    public function publish(string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::findOrFail($campaign);

            // Validate campaign has required content
            if (empty($campaignModel->content)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot publish campaign without content',
                ], 422);
            }

            $campaignModel->publish();

            return response()->json([
                'success' => true,
                'message' => 'Campaign published successfully',
                'data' => $campaignModel->fresh(),
            ]);
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
     * POST /admin/helpdesk/campaigns/{campaign}/pause
     */
    public function pause(string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::findOrFail($campaign);

            if ($campaignModel->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active campaigns can be paused',
                ], 422);
            }

            $campaignModel->pause();

            return response()->json([
                'success' => true,
                'message' => 'Campaign paused successfully',
                'data' => $campaignModel->fresh(),
            ]);
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
     * POST /admin/helpdesk/campaigns/{campaign}/resume
     */
    public function resume(string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::findOrFail($campaign);

            if ($campaignModel->status !== 'paused') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only paused campaigns can be resumed',
                ], 422);
            }

            $campaignModel->resume();

            return response()->json([
                'success' => true,
                'message' => 'Campaign resumed successfully',
                'data' => $campaignModel->fresh(),
            ]);
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
     * POST /admin/helpdesk/campaigns/{campaign}/end
     */
    public function end(string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::findOrFail($campaign);

            if (! in_array($campaignModel->status, ['active', 'paused'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active or paused campaigns can be ended',
                ], 422);
            }

            $campaignModel->end();

            return response()->json([
                'success' => true,
                'message' => 'Campaign ended successfully',
                'data' => $campaignModel->fresh(),
            ]);
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
     * GET /admin/helpdesk/campaigns/{campaign}/statistics
     */
    public function statistics(Request $request, string $campaign): JsonResponse
    {
        try {
            $campaignModel = Campaign::with('impressions')->findOrFail($campaign);

            // Get date range from request or default to last 30 days
            $startDate = $request->get('start_date', now()->subDays(30));
            $endDate = $request->get('end_date', now());

            // Basic stats
            $totalImpressions = $campaignModel->impressions()->count();
            $totalClicks = $campaignModel->impressions()->clicked()->count();
            $ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;

            // Stats by device
            $deviceStats = $campaignModel->impressions()
                ->select('device_type', DB::raw('COUNT(*) as count'))
                ->groupBy('device_type')
                ->get()
                ->pluck('count', 'device_type');

            // Stats by country
            $countryStats = $campaignModel->impressions()
                ->select('country', DB::raw('COUNT(*) as count'))
                ->groupBy('country')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'country');

            // Daily impressions trend (last 30 days)
            $dailyImpressions = $campaignModel->impressions()
                ->where('created_at', '>=', now()->subDays(30))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date');

            // Daily clicks trend (last 30 days)
            $dailyClicks = $campaignModel->impressions()
                ->whereNotNull('clicked_at')
                ->where('clicked_at', '>=', now()->subDays(30))
                ->select(DB::raw('DATE(clicked_at) as date'), DB::raw('COUNT(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date');

            // Average time to click (in seconds)
            $avgTimeToClick = $campaignModel->impressions()
                ->clicked()
                ->get()
                ->avg(function ($impression) {
                    return $impression->time_to_click;
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'campaign' => [
                        'id' => $campaignModel->id,
                        'name' => $campaignModel->name,
                        'type' => $campaignModel->type,
                        'status' => $campaignModel->status,
                    ],
                    'overview' => [
                        'total_impressions' => $totalImpressions,
                        'total_clicks' => $totalClicks,
                        'ctr' => $ctr,
                        'average_daily_impressions' => $campaignModel->average_daily_impressions,
                        'average_time_to_click' => $avgTimeToClick ? round($avgTimeToClick, 2) : null,
                    ],
                    'devices' => $deviceStats,
                    'countries' => $countryStats,
                    'trends' => [
                        'daily_impressions' => $dailyImpressions,
                        'daily_clicks' => $dailyClicks,
                    ],
                ],
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
     * POST /admin/helpdesk/campaigns/{campaign}/duplicate
     */
    public function duplicate(string $campaign): JsonResponse
    {
        try {
            $originalCampaign = Campaign::findOrFail($campaign);

            // Create duplicate
            $duplicateCampaign = Campaign::create([
                'name' => $originalCampaign->name.' (Copy)',
                'description' => $originalCampaign->description,
                'type' => $originalCampaign->type,
                'status' => 'draft', // Always create as draft
                'content' => $originalCampaign->content,
                'appearance' => $originalCampaign->appearance,
                'conditions' => $originalCampaign->conditions,
                'metadata' => $originalCampaign->metadata,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign duplicated successfully',
                'data' => $duplicateCampaign,
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
