<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Subscriber\Entities\Subscriber;

/**
 * API Controller for managing subscribers.
 *
 * Provides RESTful endpoints for subscriber CRUD operations and management actions.
 * All endpoints require authentication via Laravel Sanctum (auth:sanctum).
 */
class SubscriberController extends Controller
{
    /**
     * Display all authenticated user's subscribers.
     *
     * GET /api/subscribers
     *
     * @return JsonResponse List of subscribers with basic information
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $perPage = $request->get('per_page', 15);

            $subscribers = Subscriber::query()
                ->select('id', 'uid', 'email', 'first_name', 'last_name', 'status', 'created_at', 'updated_at')
                ->where('customer_id', $user->customer_id)
                ->paginate($perPage);

            return response()->json($subscribers, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Create a new subscriber.
     *
     * POST /api/subscribers
     *
     * @return JsonResponse Created subscriber data
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();

            // Validate
            $validator = \Validator::make($request->all(), [
                'email' => 'required|email|unique:subscribers,email',
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'status' => 'sometimes|string|in:subscribed,unsubscribed,pending',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 422);
            }

            $subscriber = new Subscriber;
            $subscriber->customer_id = $user->customer_id;
            $subscriber->email = $request->get('email');
            $subscriber->first_name = $request->get('first_name', '');
            $subscriber->last_name = $request->get('last_name', '');
            $subscriber->status = $request->get('status', 'subscribed');
            $subscriber->save();

            return response()->json([
                'status' => 1,
                'message' => 'Subscriber created successfully',
                'data' => $subscriber,
            ], 201);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Display the specified subscriber.
     *
     * GET /api/subscribers/{uid}
     *
     * @param  string  $uid  Subscriber's UID
     * @return JsonResponse Subscriber data with details
     */
    public function show(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $subscriber = Subscriber::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $subscriber) {
                return response()->json(['message' => 'Subscriber not found'], 404);
            }

            $subscriberData = [
                'uid' => $subscriber->uid,
                'email' => $subscriber->email,
                'first_name' => $subscriber->first_name,
                'last_name' => $subscriber->last_name,
                'status' => $subscriber->status,
                'created_at' => $subscriber->created_at,
                'updated_at' => $subscriber->updated_at,
            ];

            return response()->json([
                'data' => $subscriberData,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Update the specified subscriber.
     *
     * PUT /api/subscribers/{uid}
     *
     * @param  string  $uid  Subscriber's UID
     * @return JsonResponse Updated subscriber data
     */
    public function update(Request $request, string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $subscriber = Subscriber::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $subscriber) {
                return response()->json(['message' => 'Subscriber not found'], 404);
            }

            // Validate
            $validator = \Validator::make($request->all(), [
                'email' => 'sometimes|email|unique:subscribers,email,'.$subscriber->id,
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'status' => 'sometimes|string|in:subscribed,unsubscribed,pending',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 422);
            }

            // Update from array
            $subscriber->update($request->all());

            return response()->json([
                'status' => 1,
                'message' => 'Subscriber updated successfully',
                'data' => $subscriber,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Delete the specified subscriber.
     *
     * DELETE /api/subscribers/{uid}
     *
     * @param  string  $uid  Subscriber's UID
     * @return JsonResponse Success message
     */
    public function delete(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $subscriber = Subscriber::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $subscriber) {
                return response()->json(['status' => 0, 'message' => 'Subscriber not found'], 404);
            }

            $subscriber->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Subscriber deleted successfully',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Get activity logs for the specified subscriber.
     *
     * GET /api/subscribers/{uid}/logs
     *
     * @param  string  $uid  Subscriber's UID
     * @return JsonResponse Subscriber activity logs
     */
    public function logs(Request $request, string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $subscriber = Subscriber::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $subscriber) {
                return response()->json(['message' => 'Subscriber not found'], 404);
            }

            $perPage = $request->get('per_page', 15);

            // Get activity logs (adjust based on your actual activity log table)
            $logs = $subscriber->activities()
                ->paginate($perPage);

            return response()->json([
                'data' => $logs,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
