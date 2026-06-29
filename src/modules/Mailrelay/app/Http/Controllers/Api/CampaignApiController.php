<?php

namespace Modules\Mailrelay\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Enums\CampaignStatus;
use Modules\Mailrelay\Services\MailRelayService;

/**
 * CampaignApiController
 *
 * API controller for campaign management endpoints.
 * Handles campaign CRUD operations, sending, and analytics via MailRelayService.
 *
 * Reference: FASE 8 - Section 8.4
 *
 * Endpoints:
 * - GET    /api/v1/campaigns          - List all campaigns
 * - POST   /api/v1/campaigns          - Create new campaign
 * - GET    /api/v1/campaigns/{id}     - Show campaign details
 * - PUT    /api/v1/campaigns/{id}     - Update campaign
 * - DELETE /api/v1/campaigns/{id}     - Delete campaign
 * - POST   /api/v1/campaigns/{id}/send - Send campaign
 * - GET    /api/v1/campaigns/{id}/analytics - Get campaign analytics
 */
class CampaignApiController extends Controller
{
    private MailRelayService $mailRelayService;

    public function __construct(MailRelayService $mailRelayService)
    {
        $this->mailRelayService = $mailRelayService;
    }

    /**
     * List all campaigns
     *
     * @return JsonResponse
     *
     * Query parameters:
     * - status: Filter by status (draft, scheduled, sending, sent, failed, cancelled)
     * - per_page: Number of items per page (default: 15, max: 100)
     * - page: Page number
     * - sort: Sort field (default: created_at)
     * - order: Sort order (asc, desc, default: desc)
     *
     * Response:
     * {
     *   "data": [
     *     {
     *       "id": "01HMX...",
     *       "name": "Monthly Newsletter",
     *       "subject": "Your Monthly Update",
     *       "status": "draft",
     *       "recipient_count": 150,
     *       "scheduled_at": null,
     *       "sent_at": null,
     *       "created_at": "2025-01-15T10:30:00Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "total": 25,
     *     "per_page": 15
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:draft,scheduled,sending,sent,failed,cancelled',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'sort' => 'sometimes|string|in:name,created_at,updated_at,scheduled_at,sent_at',
            'order' => 'sometimes|string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $perPage = $request->input('per_page', 15);
            $sort = $request->input('sort', 'created_at');
            $order = $request->input('order', 'desc');

            $query = Campaign::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Apply sorting
            $query->orderBy($sort, $order);

            // Paginate results
            $campaigns = $query->paginate($perPage);

            return response()->json([
                'data' => $campaigns->items(),
                'meta' => [
                    'current_page' => $campaigns->currentPage(),
                    'total' => $campaigns->total(),
                    'per_page' => $campaigns->perPage(),
                    'last_page' => $campaigns->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('CampaignApiController: Error listing campaigns', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve campaigns',
                'message' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Create a new campaign
     *
     * @return JsonResponse
     *
     * Request body:
     * {
     *   "name": "Monthly Newsletter",
     *   "subject": "Your Monthly Update",
     *   "html_content": "<html>...</html>",
     *   "plain_content": "Plain text version", // optional
     *   "sender_id": 123, // optional
     *   "metadata": {}, // optional
     *   "create_in_mailrelay": true // optional, default: false
     * }
     *
     * Response:
     * {
     *   "data": {
     *     "id": "01HMX...",
     *     "name": "Monthly Newsletter",
     *     "subject": "Your Monthly Update",
     *     "status": "draft",
     *     "mailrelay_campaign_id": 456, // if created in Mailrelay
     *     "created_at": "2025-01-15T10:30:00Z"
     *   },
     *   "message": "Campaign created successfully"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'html_content' => 'required|string',
            'plain_content' => 'sometimes|string',
            'sender_id' => 'sometimes|integer',
            'metadata' => 'sometimes|array',
            'create_in_mailrelay' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            // Create campaign in local database
            $campaign = Campaign::create([
                'name' => $request->input('name'),
                'subject' => $request->input('subject'),
                'html_content' => $request->input('html_content'),
                'plain_content' => $request->input('plain_content'),
                'sender_id' => $request->input('sender_id'),
                'status' => CampaignStatus::DRAFT,
                'metadata' => $request->input('metadata', []),
            ]);

            // Optionally create in Mailrelay
            if ($request->input('create_in_mailrelay', false)) {
                $mailrelayResponse = $this->mailRelayService->createCampaign($campaign);

                Log::info('CampaignApiController: Campaign created in Mailrelay', [
                    'campaign_id' => $campaign->id,
                    'mailrelay_campaign_id' => $campaign->mailrelay_campaign_id,
                ]);
            }

            return response()->json([
                'data' => $campaign,
                'message' => 'Campaign created successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('CampaignApiController: Error creating campaign', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to create campaign',
                'message' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Show campaign details
     *
     * @param  string  $id  Campaign ULID
     * @return JsonResponse
     *
     * Response:
     * {
     *   "data": {
     *     "id": "01HMX...",
     *     "mailrelay_campaign_id": 456,
     *     "name": "Monthly Newsletter",
     *     "subject": "Your Monthly Update",
     *     "html_content": "<html>...</html>",
     *     "plain_content": "Plain text version",
     *     "sender_id": 123,
     *     "status": "draft",
     *     "scheduled_at": null,
     *     "sent_at": null,
     *     "recipient_count": 0,
     *     "metadata": {},
     *     "created_at": "2025-01-15T10:30:00Z",
     *     "updated_at": "2025-01-15T10:30:00Z",
     *     "analytics": {
     *       "sent_count": 0,
     *       "opened_count": 0,
     *       "clicked_count": 0,
     *       "bounced_count": 0,
     *       "open_rate": 0,
     *       "click_rate": 0
     *     }
     *   }
     * }
     */
    public function show(string $id): JsonResponse
    {
        try {
            $campaign = Campaign::with('analytics')->findOrFail($id);

            return response()->json([
                'data' => $campaign,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Campaign not found',
                'message' => "Campaign with ID {$id} does not exist",
            ], 404);

        } catch (\Exception $e) {
            Log::error('CampaignApiController: Error retrieving campaign', [
                'campaign_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve campaign',
                'message' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Update a campaign
     *
     * @param  string  $id  Campaign ULID
     * @return JsonResponse
     *
     * Request body:
     * {
     *   "name": "Updated Newsletter",
     *   "subject": "Your Updated Newsletter",
     *   "html_content": "<html>...</html>",
     *   "plain_content": "Plain text version",
     *   "sender_id": 123,
     *   "metadata": {},
     *   "sync_to_mailrelay": true // optional, default: false
     * }
     *
     * Response:
     * {
     *   "data": {
     *     "id": "01HMX...",
     *     "name": "Updated Newsletter",
     *     ...
     *   },
     *   "message": "Campaign updated successfully"
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'html_content' => 'sometimes|string',
            'plain_content' => 'sometimes|string',
            'sender_id' => 'sometimes|integer',
            'metadata' => 'sometimes|array',
            'sync_to_mailrelay' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $campaign = Campaign::findOrFail($id);

            // Check if campaign can be edited
            if (! $campaign->status->canEdit()) {
                return response()->json([
                    'error' => 'Campaign cannot be edited',
                    'message' => "Campaign with status '{$campaign->status->value}' cannot be edited",
                ], 422);
            }

            // Update campaign fields
            $updateData = $request->only([
                'name',
                'subject',
                'html_content',
                'plain_content',
                'sender_id',
                'metadata',
            ]);

            $campaign->update($updateData);

            // Optionally sync to Mailrelay
            if ($request->input('sync_to_mailrelay', false) && $campaign->mailrelay_campaign_id) {
                $this->mailRelayService->createCampaign($campaign, $updateData);

                Log::info('CampaignApiController: Campaign synced to Mailrelay', [
                    'campaign_id' => $campaign->id,
                    'mailrelay_campaign_id' => $campaign->mailrelay_campaign_id,
                ]);
            }

            return response()->json([
                'data' => $campaign->fresh(),
                'message' => 'Campaign updated successfully',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Campaign not found',
                'message' => "Campaign with ID {$id} does not exist",
            ], 404);

        } catch (\Exception $e) {
            Log::error('CampaignApiController: Error updating campaign', [
                'campaign_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to update campaign',
                'message' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Delete a campaign
     *
     * @param  string  $id  Campaign ULID
     * @return JsonResponse
     *
     * Response:
     * {
     *   "message": "Campaign deleted successfully"
     * }
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);

            // Check if campaign can be deleted
            if ($campaign->status === CampaignStatus::SENDING) {
                return response()->json([
                    'error' => 'Campaign cannot be deleted',
                    'message' => 'Cannot delete a campaign that is currently being sent',
                ], 422);
            }

            // Soft delete the campaign
            $campaign->delete();

            Log::info('CampaignApiController: Campaign deleted', [
                'campaign_id' => $id,
            ]);

            return response()->json([
                'message' => 'Campaign deleted successfully',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Campaign not found',
                'message' => "Campaign with ID {$id} does not exist",
            ], 404);

        } catch (\Exception $e) {
            Log::error('CampaignApiController: Error deleting campaign', [
                'campaign_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to delete campaign',
                'message' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Send a campaign via Mailrelay
     *
     * @param  string  $id  Campaign ULID
     * @return JsonResponse
     *
     * Request body:
     * {
     *   "groups": [1, 2, 3], // optional, Mailrelay group IDs
     *   "test_email": "test@example.com", // optional, for test sends
     *   "scheduled_at": "2025-01-20T14:00:00Z", // optional, schedule for later
     *   "create_in_mailrelay": true // optional, create if not exists
     * }
     *
     * Response:
     * {
     *   "data": {
     *     "campaign_id": "01HMX...",
     *     "status": "sending",
     *     "mailrelay_response": {...}
     *   },
     *   "message": "Campaign sent successfully"
     * }
     */
    public function send(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'groups' => 'sometimes|array',
            'groups.*' => 'integer',
            'test_email' => 'sometimes|email',
            'scheduled_at' => 'sometimes|date',
            'create_in_mailrelay' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $campaign = Campaign::findOrFail($id);

            // Check if campaign can be sent
            if (! $campaign->status->canSend()) {
                return response()->json([
                    'error' => 'Campaign cannot be sent',
                    'message' => "Campaign with status '{$campaign->status->value}' cannot be sent",
                ], 422);
            }

            // Create in Mailrelay if it doesn't exist and requested
            if (! $campaign->mailrelay_campaign_id && $request->input('create_in_mailrelay', true)) {
                $this->mailRelayService->createCampaign($campaign);
                $campaign->refresh();
            }

            // Check if campaign exists in Mailrelay
            if (! $campaign->mailrelay_campaign_id) {
                return response()->json([
                    'error' => 'Campaign not created in Mailrelay',
                    'message' => 'Campaign must be created in Mailrelay before sending',
                ], 422);
            }

            // Handle scheduled send
            if ($request->has('scheduled_at')) {
                $scheduledAt = new \DateTime($request->input('scheduled_at'));
                $campaign->schedule($scheduledAt);

                return response()->json([
                    'data' => $campaign->fresh(),
                    'message' => 'Campaign scheduled successfully',
                ], 200);
            }

            // Prepare send options
            $sendOptions = [];
            if ($request->has('groups')) {
                $sendOptions['groups'] = $request->input('groups');
            }
            if ($request->has('test_email')) {
                $sendOptions['test_email'] = $request->input('test_email');
            }

            // Send campaign via Mailrelay
            $mailrelayResponse = $this->mailRelayService->sendCampaign($campaign, $sendOptions);

            Log::info('CampaignApiController: Campaign sent', [
                'campaign_id' => $campaign->id,
                'mailrelay_campaign_id' => $campaign->mailrelay_campaign_id,
            ]);

            return response()->json([
                'data' => [
                    'campaign_id' => $campaign->id,
                    'status' => $campaign->fresh()->status->value,
                    'mailrelay_response' => $mailrelayResponse,
                ],
                'message' => 'Campaign sent successfully',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Campaign not found',
                'message' => "Campaign with ID {$id} does not exist",
            ], 404);

        } catch (\Exception $e) {
            Log::error('CampaignApiController: Error sending campaign', [
                'campaign_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to send campaign',
                'message' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Get campaign analytics from Mailrelay
     *
     * @param  string  $id  Campaign ULID
     * @return JsonResponse
     *
     * Response:
     * {
     *   "data": {
     *     "campaign_id": "01HMX...",
     *     "mailrelay_campaign_id": 456,
     *     "sent_count": 1500,
     *     "opened_count": 750,
     *     "clicked_count": 300,
     *     "bounced_count": 25,
     *     "unsubscribed_count": 10,
     *     "open_rate": 50.00,
     *     "click_rate": 20.00,
     *     "last_synced_at": "2025-01-15T10:30:00Z"
     *   }
     * }
     */
    public function analytics(string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);

            // Check if campaign has been sent
            if (! $campaign->isSent()) {
                return response()->json([
                    'error' => 'Analytics not available',
                    'message' => 'Campaign has not been sent yet',
                ], 422);
            }

            // Check if campaign exists in Mailrelay
            if (! $campaign->mailrelay_campaign_id) {
                return response()->json([
                    'error' => 'Analytics not available',
                    'message' => 'Campaign was not sent via Mailrelay',
                ], 422);
            }

            // Sync analytics from Mailrelay
            $synced = $this->mailRelayService->syncCampaignAnalytics($campaign);

            if (! $synced) {
                return response()->json([
                    'error' => 'Failed to sync analytics',
                    'message' => 'Unable to retrieve analytics from Mailrelay',
                ], 500);
            }

            // Get updated analytics
            $campaign->load('analytics');
            $analytics = $campaign->analytics;

            if (! $analytics) {
                return response()->json([
                    'error' => 'Analytics not available',
                    'message' => 'No analytics data found for this campaign',
                ], 404);
            }

            return response()->json([
                'data' => [
                    'campaign_id' => $campaign->id,
                    'mailrelay_campaign_id' => $campaign->mailrelay_campaign_id,
                    'sent_count' => $analytics->sent_count,
                    'opened_count' => $analytics->opened_count,
                    'clicked_count' => $analytics->clicked_count,
                    'bounced_count' => $analytics->bounced_count,
                    'unsubscribed_count' => $analytics->unsubscribed_count,
                    'open_rate' => $analytics->open_rate,
                    'click_rate' => $analytics->click_rate,
                    'last_synced_at' => $analytics->last_synced_at,
                ],
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Campaign not found',
                'message' => "Campaign with ID {$id} does not exist",
            ], 404);

        } catch (\Exception $e) {
            Log::error('CampaignApiController: Error retrieving campaign analytics', [
                'campaign_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve campaign analytics',
                'message' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }
}
