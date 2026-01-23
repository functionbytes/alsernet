<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Entities\Campaign;

/**
 * API Controller for managing campaigns.
 *
 * Provides RESTful endpoints for campaign CRUD operations and management actions.
 * All endpoints require authentication via Laravel Sanctum (auth:sanctum).
 */
class CampaignController extends Controller
{
    /**
     * Display all authenticated user's campaigns.
     *
     * GET /api/campaigns
     *
     * @return JsonResponse List of campaigns with basic information
     */
    public function index(): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();

            $campaigns = Campaign::query()
                ->select('id', 'uid', 'name', 'type', 'subject', 'plain', 'from_email', 'from_name', 'reply_to', 'status', 'delivery_at', 'created_at', 'updated_at')
                ->where('customer_id', $user->customer_id)
                ->get();

            return response()->json(['data' => $campaigns], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Create a new campaign.
     *
     * POST /api/campaigns
     *
     * @return JsonResponse Created campaign data
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $campaign = $user->customer->newDefaultCampaign();

            // Authorize
            if (! $user->can('create', $campaign)) {
                return response()->json(['status' => 0, 'message' => 'Unauthorized'], 403);
            }

            // Validate
            $validator = \Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'from_email' => 'required|email',
                'from_name' => 'required|string|max:255',
                'reply_to' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 422);
            }

            // Save from array
            $campaign->saveFromArray($request->all());

            return response()->json([
                'status' => 1,
                'message' => 'Campaign created successfully',
                'data' => $campaign,
            ], 201);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Display the specified campaign.
     *
     * GET /api/campaigns/{id}
     *
     * @param  string  $id  Campaign's UID
     * @return JsonResponse Campaign data with statistics
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $campaign = Campaign::where('uid', $id)->first();

            // Check if item exists
            if (! $campaign) {
                return response()->json(['message' => 'Campaign not found'], 404);
            }

            // Authorize
            if (! $user->can('read', $campaign)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $campaignData = [
                'uid' => $campaign->uid,
                'name' => $campaign->name,
                'list' => $campaign->mailList ? $campaign->mailList->name : null,
                'segment' => $campaign->segment ? $campaign->segment->name : null,
                'from_email' => $campaign->from_email,
                'from_name' => $campaign->from_name,
                'remind_message' => $campaign->remind_message,
                'status' => $campaign->status,
                'created_at' => $campaign->created_at,
                'updated_at' => $campaign->updated_at,
            ];

            $statistics = [
                'subscriber_count' => $campaign->subscribersCount(),
                'delivered_count' => $campaign->deliveredCount(),
                'delivered_rate' => $campaign->deliveredRate(),
                'unique_open_count' => $campaign->uniqueOpenCount(),
                'open_rate' => $campaign->openRate(),
                'click_count' => $campaign->clickCount(),
                'click_rate' => $campaign->clickRate(),
                'bounce_count' => $campaign->bounceCount(),
                'unsubscribe_count' => $campaign->unsubscribeCount(),
                'abuse_feedback_count' => $campaign->abuseFeedbackCount(),
            ];

            return response()->json([
                'data' => $campaignData,
                'statistics' => $statistics,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Update the specified campaign.
     *
     * PUT /api/campaigns/{id}
     *
     * @param  string  $id  Campaign's UID
     * @return JsonResponse Updated campaign data
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $campaign = Campaign::where('uid', $id)->first();

            // Check if item exists
            if (! $campaign) {
                return response()->json(['message' => 'Campaign not found'], 404);
            }

            // Authorize
            if (! $user->can('update', $campaign)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Validate
            $validator = \Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'subject' => 'sometimes|string|max:255',
                'from_email' => 'sometimes|email',
                'from_name' => 'sometimes|string|max:255',
                'reply_to' => 'sometimes|email',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 422);
            }

            // Update from array
            $campaign->saveFromArray($request->all());

            return response()->json([
                'status' => 1,
                'message' => 'Campaign updated successfully',
                'data' => $campaign,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Pause a campaign.
     *
     * POST /api/campaigns/{id}/pause
     *
     * @param  string  $id  Campaign's UID
     * @return JsonResponse Campaign data with updated status
     */
    public function pause(string $id): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $campaign = Campaign::where('uid', $id)->first();

            // Check if item exists
            if (! $campaign) {
                return response()->json(['message' => 'Campaign not found'], 404);
            }

            // Authorize
            if (! $user->can('pause', $campaign)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $campaign->pause();

            $campaignData = [
                'uid' => $campaign->uid,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'updated_at' => $campaign->updated_at,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Campaign paused successfully',
                'data' => $campaignData,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 'error', 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Execute/Run a campaign.
     *
     * POST /api/campaigns/{id}/run
     *
     * @param  string  $id  Campaign's UID
     * @return JsonResponse Campaign data with updated status
     */
    public function run(string $id): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $campaign = Campaign::where('uid', $id)->first();

            // Check if item exists
            if (! $campaign) {
                return response()->json(['message' => 'Campaign not found'], 404);
            }

            // Authorize
            if (! $user->can('run', $campaign)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $campaign->execute();

            $campaignData = [
                'uid' => $campaign->uid,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'updated_at' => $campaign->updated_at,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Campaign executed successfully',
                'data' => $campaignData,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 'error', 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Resume a paused campaign.
     *
     * POST /api/campaigns/{id}/resume
     *
     * @param  string  $id  Campaign's UID
     * @return JsonResponse Campaign data with updated status
     */
    public function resume(string $id): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $campaign = Campaign::where('uid', $id)->first();

            // Check if item exists
            if (! $campaign) {
                return response()->json(['message' => 'Campaign not found'], 404);
            }

            // Authorize
            if (! $user->can('restart', $campaign)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $campaign->resume();

            $campaignData = [
                'uid' => $campaign->uid,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'updated_at' => $campaign->updated_at,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Campaign resumed successfully',
                'data' => $campaignData,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 'error', 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Delete a campaign.
     *
     * DELETE /api/campaigns/{id}
     *
     * @param  string  $id  Campaign's UID
     * @return JsonResponse Success message
     */
    public function delete(string $id): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $campaign = Campaign::where('uid', $id)->first();

            // Check if item exists
            if (! $campaign) {
                return response()->json(['status' => 0, 'message' => 'Campaign not found'], 404);
            }

            // Authorize
            if (! $user->can('delete', $campaign)) {
                return response()->json(['status' => 0, 'message' => 'Unauthorized'], 403);
            }

            $campaign->deleteAndCleanup();

            return response()->json([
                'status' => 1,
                'message' => 'Campaign deleted successfully',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }
}
