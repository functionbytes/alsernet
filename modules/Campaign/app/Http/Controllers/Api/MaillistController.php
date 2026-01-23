<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Entities\CampaignMaillist;
use Modules\Campaign\Entities\CampaignMaillistsSubscriber;

/**
 * API Controller for managing maillists.
 *
 * Provides RESTful endpoints for maillist CRUD operations and management actions.
 * All endpoints require authentication via Laravel Sanctum (auth:sanctum).
 */
class MaillistController extends Controller
{
    /**
     * Display all authenticated user's maillists.
     *
     * GET /api/maillists
     *
     * @return JsonResponse List of maillists with basic information
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $perPage = $request->get('per_page', 15);

            $maillists = CampaignMaillist::query()
                ->select('id', 'uid', 'name', 'description', 'subscriber_count', 'created_at', 'updated_at')
                ->where('customer_id', $user->customer_id)
                ->paginate($perPage);

            return response()->json($maillists, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Create a new maillist.
     *
     * POST /api/maillists
     *
     * @return JsonResponse Created maillist data
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();

            // Validate
            $validator = \Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'sometimes|string',
                'from_email' => 'required|email',
                'from_name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 422);
            }

            $maillist = new CampaignMaillist;
            $maillist->customer_id = $user->customer_id;
            $maillist->name = $request->get('name');
            $maillist->description = $request->get('description', '');
            $maillist->from_email = $request->get('from_email');
            $maillist->from_name = $request->get('from_name');
            $maillist->save();

            return response()->json([
                'status' => 1,
                'message' => 'Maillist created successfully',
                'data' => $maillist,
            ], 201);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Display the specified maillist.
     *
     * GET /api/maillists/{uid}
     *
     * @param  string  $uid  Maillist's UID
     * @return JsonResponse Maillist data with statistics
     */
    public function show(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $maillist = CampaignMaillist::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $maillist) {
                return response()->json(['message' => 'Maillist not found'], 404);
            }

            $maillistData = [
                'uid' => $maillist->uid,
                'name' => $maillist->name,
                'description' => $maillist->description,
                'from_email' => $maillist->from_email,
                'from_name' => $maillist->from_name,
                'subscriber_count' => $maillist->subscriber_count ?? 0,
                'created_at' => $maillist->created_at,
                'updated_at' => $maillist->updated_at,
            ];

            return response()->json([
                'data' => $maillistData,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Update the specified maillist.
     *
     * PUT /api/maillists/{uid}
     *
     * @param  string  $uid  Maillist's UID
     * @return JsonResponse Updated maillist data
     */
    public function update(Request $request, string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $maillist = CampaignMaillist::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $maillist) {
                return response()->json(['message' => 'Maillist not found'], 404);
            }

            // Validate
            $validator = \Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'from_email' => 'sometimes|email',
                'from_name' => 'sometimes|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 422);
            }

            // Update from array
            $maillist->update($request->all());

            return response()->json([
                'status' => 1,
                'message' => 'Maillist updated successfully',
                'data' => $maillist,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Delete the specified maillist.
     *
     * DELETE /api/maillists/{uid}
     *
     * @param  string  $uid  Maillist's UID
     * @return JsonResponse Success message
     */
    public function delete(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $maillist = CampaignMaillist::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $maillist) {
                return response()->json(['status' => 0, 'message' => 'Maillist not found'], 404);
            }

            $maillist->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Maillist deleted successfully',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Get subscribers for the specified maillist.
     *
     * GET /api/maillists/{uid}/subscribers
     *
     * @param  string  $uid  Maillist's UID
     * @return JsonResponse List of subscribers in the maillist
     */
    public function subscribers(Request $request, string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $maillist = CampaignMaillist::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $maillist) {
                return response()->json(['message' => 'Maillist not found'], 404);
            }

            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');

            $query = CampaignMaillistsSubscriber::query()
                ->where('maillist_id', $maillist->id)
                ->select('id', 'subscriber_id', 'status', 'created_at');

            if ($status) {
                $query->where('status', $status);
            }

            $subscribers = $query->paginate($perPage);

            return response()->json([
                'data' => $subscribers,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Start email verification for the specified maillist.
     *
     * POST /api/maillists/{uid}/verify
     *
     * @param  string  $uid  Maillist's UID
     * @return JsonResponse Status of verification job
     */
    public function verify(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $maillist = CampaignMaillist::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $maillist) {
                return response()->json(['message' => 'Maillist not found'], 404);
            }

            // Authorize
            if (! $user->can('verify', $maillist)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Start verification (adjust based on your actual implementation)
            // $maillist->startVerification();

            return response()->json([
                'status' => 'success',
                'message' => 'Email verification started for maillist',
                'data' => [
                    'uid' => $maillist->uid,
                    'name' => $maillist->name,
                ],
            ], 202);
        } catch (\Exception $exception) {
            return response()->json(['status' => 'error', 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Get statistics for the specified maillist.
     *
     * GET /api/maillists/{uid}/stats
     *
     * @param  string  $uid  Maillist's UID
     * @return JsonResponse Statistics for the maillist
     */
    public function stats(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $maillist = CampaignMaillist::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $maillist) {
                return response()->json(['message' => 'Maillist not found'], 404);
            }

            // Get statistics
            $totalSubscribers = CampaignMaillistsSubscriber::where('maillist_id', $maillist->id)->count();
            $activeSubscribers = CampaignMaillistsSubscriber::where('maillist_id', $maillist->id)
                ->where('status', 'subscribed')
                ->count();
            $unsubscribedCount = CampaignMaillistsSubscriber::where('maillist_id', $maillist->id)
                ->where('status', 'unsubscribed')
                ->count();

            $statistics = [
                'total_subscribers' => $totalSubscribers,
                'active_subscribers' => $activeSubscribers,
                'unsubscribed_count' => $unsubscribedCount,
                'growth_rate' => $this->calculateGrowthRate($maillist->id),
            ];

            return response()->json([
                'data' => $statistics,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Calculate the growth rate for a maillist.
     */
    private function calculateGrowthRate(int $maillistId): float
    {
        // Implement growth rate calculation logic
        // This is a placeholder - adjust based on your requirements
        return 0.0;
    }
}
