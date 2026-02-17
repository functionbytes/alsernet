<?php

namespace Modules\Mailrelay\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Http\Resources\V1\CampaignResource;
use Modules\Mailrelay\Services\CampaignService;

class CampaignApiController extends Controller
{
    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Display a listing of campaigns.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $query = Campaign::with(['mailerTemplate', 'mailProvider', 'language']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by provider
        if ($request->filled('provider_id')) {
            $query->where('mail_provider_id', $request->provider_id);
        }

        // Filter by template
        if ($request->filled('template_id')) {
            $query->where('mailer_template_id', $request->template_id);
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('subject', 'like', "%{$request->search}%");
            });
        }

        // Order
        $query->orderBy('created_at', 'desc');

        $campaigns = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CampaignResource::collection($campaigns),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
                'last_page' => $campaigns->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'mailer_template_id' => 'nullable|exists:mailer_templates,id',
            'html_content' => 'nullable|string',
            'lang_id' => 'nullable|exists:langs,id',
            'mail_provider_id' => 'required|exists:mail_providers,id',
            'template_variables' => 'nullable|array',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
        ]);

        try {
            $campaign = $this->campaignService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => new CampaignResource($campaign),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified campaign.
     */
    public function show(int $id): JsonResponse
    {
        $campaign = Campaign::with(['mailerTemplate', 'mailProvider', 'language'])
            ->findOrFail($id);

        $this->authorize('view', $campaign);

        return response()->json([
            'success' => true,
            'data' => new CampaignResource($campaign),
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'mailer_template_id' => 'nullable|exists:mailer_templates,id',
            'html_content' => 'nullable|string',
            'lang_id' => 'nullable|exists:langs,id',
            'mail_provider_id' => 'required|exists:mail_providers,id',
            'template_variables' => 'nullable|array',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
        ]);

        try {
            $this->campaignService->update($campaign, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data' => new CampaignResource($campaign->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->authorize('delete', $campaign);

        try {
            $this->campaignService->delete($campaign);

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Duplicate the specified campaign.
     */
    public function duplicate(int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->authorize('duplicate', $campaign);

        try {
            $newCampaign = $this->campaignService->duplicate($campaign);

            return response()->json([
                'success' => true,
                'message' => 'Campaign duplicated successfully',
                'data' => new CampaignResource($newCampaign),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate campaign',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get campaign preview.
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->authorize('preview', $campaign);

        try {
            $html = $this->campaignService->getPreview($campaign, $request->input('variables', []));

            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $html,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate preview',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Send test email.
     */
    public function sendTest(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->authorize('sendTest', $campaign);

        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            $result = $this->campaignService->sendTest($campaign, $validated['test_email']);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Send campaign.
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->authorize('send', $campaign);

        $validated = $request->validate([
            'recipients' => 'required|array|min:1',
            'recipients.*.email' => 'required|email',
            'send_async' => 'boolean',
        ]);

        try {
            if ($validated['send_async'] ?? false) {
                $this->campaignService->sendAsync($campaign, $validated['recipients']);

                return response()->json([
                    'success' => true,
                    'message' => 'Campaign queued for sending',
                    'async' => true,
                ]);
            } else {
                $result = $this->campaignService->send($campaign, $validated['recipients']);

                return response()->json($result);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send campaign',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Schedule campaign.
     */
    public function schedule(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->authorize('schedule', $campaign);

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'recipients' => 'required|array|min:1',
        ]);

        try {
            $scheduledAt = new \DateTime($validated['scheduled_at']);
            $this->campaignService->schedule($campaign, $scheduledAt);

            return response()->json([
                'success' => true,
                'message' => 'Campaign scheduled successfully',
                'data' => [
                    'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule campaign',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get campaign stats.
     */
    public function stats(int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->authorize('viewAnalytics', $campaign);

        try {
            $stats = $this->campaignService->getStats($campaign);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve campaign stats',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
