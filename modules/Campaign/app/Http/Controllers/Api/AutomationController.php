<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunAutomation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Entities\Automation\Automation;

/**
 * API Controller for managing automations.
 *
 * Provides RESTful endpoints for automation CRUD operations and management actions.
 * All endpoints require authentication via Laravel Sanctum (auth:sanctum).
 */
class AutomationController extends Controller
{
    /**
     * Display all authenticated user's automations.
     *
     * GET /api/automations
     *
     * @return JsonResponse List of automations with basic information
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $perPage = $request->get('per_page', 15);

            $automations = Automation::query()
                ->select('id', 'uid', 'name', 'description', 'status', 'created_at', 'updated_at')
                ->where('customer_id', $user->customer_id)
                ->paginate($perPage);

            return response()->json(['data' => $automations], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Create a new automation.
     *
     * POST /api/automations
     *
     * @return JsonResponse Created automation data
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();

            // Validate
            $validator = \Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 422);
            }

            $automation = new Automation;
            $automation->customer_id = $user->customer_id;
            $automation->name = $request->get('name');
            $automation->description = $request->get('description', '');
            $automation->status = 'draft';
            $automation->save();

            return response()->json([
                'status' => 1,
                'message' => 'Automation created successfully',
                'data' => $automation,
            ], 201);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Display the specified automation.
     *
     * GET /api/automations/{uid}
     *
     * @param  string  $uid  Automation's UID
     * @return JsonResponse Automation data with configuration
     */
    public function show(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $automation = Automation::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $automation) {
                return response()->json(['message' => 'Automation not found'], 404);
            }

            $automationData = [
                'uid' => $automation->uid,
                'name' => $automation->name,
                'description' => $automation->description,
                'status' => $automation->status,
                'created_at' => $automation->created_at,
                'updated_at' => $automation->updated_at,
            ];

            return response()->json([
                'data' => $automationData,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Update the specified automation.
     *
     * PUT /api/automations/{uid}
     *
     * @param  string  $uid  Automation's UID
     * @return JsonResponse Updated automation data
     */
    public function update(Request $request, string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $automation = Automation::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $automation) {
                return response()->json(['message' => 'Automation not found'], 404);
            }

            // Validate
            $validator = \Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 422);
            }

            // Update from array
            $automation->update($request->all());

            return response()->json([
                'status' => 1,
                'message' => 'Automation updated successfully',
                'data' => $automation,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Delete the specified automation.
     *
     * DELETE /api/automations/{uid}
     *
     * @param  string  $uid  Automation's UID
     * @return JsonResponse Success message
     */
    public function delete(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $automation = Automation::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if item exists
            if (! $automation) {
                return response()->json(['status' => 0, 'message' => 'Automation not found'], 404);
            }

            $automation->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Automation deleted successfully',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Execute an automation workflow.
     *
     * POST /api/automations/{uid}/execute
     *
     * Queues the specified automation for execution as a background job.
     *
     * @param  string  $uid  Automation's UID
     * @return JsonResponse Status of the queued automation
     */
    public function execute(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $automation = Automation::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if automation exists
            if (! $automation) {
                return response()->json(['success' => false, 'error' => 'Automation not found'], 404);
            }

            // Authorize
            if (! $user->can('execute', $automation)) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }

            // Queue the automation for execution
            $automation->logger()->info(sprintf('Queuing automation "%s" in response to API call', $automation->name));
            dispatch(new RunAutomation($automation));

            return response()->json([
                'success' => true,
                'message' => 'Automation queued for execution',
                'data' => [
                    'uid' => $automation->uid,
                    'name' => $automation->name,
                    'status' => 'queued',
                ],
            ], 202);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Enable an automation workflow.
     *
     * POST /api/automations/{uid}/enable
     *
     * @param  string  $uid  Automation's UID
     * @return JsonResponse Status of the automation
     */
    public function enable(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $automation = Automation::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if automation exists
            if (! $automation) {
                return response()->json(['success' => false, 'error' => 'Automation not found'], 404);
            }

            // Authorize
            if (! $user->can('enable', $automation)) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }

            // Enable the automation
            $automation->status = 'active';
            $automation->save();

            return response()->json([
                'success' => true,
                'message' => 'Automation enabled successfully',
                'data' => [
                    'uid' => $automation->uid,
                    'name' => $automation->name,
                    'status' => $automation->status,
                ],
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Disable an automation workflow.
     *
     * POST /api/automations/{uid}/disable
     *
     * @param  string  $uid  Automation's UID
     * @return JsonResponse Status of the automation
     */
    public function disable(string $uid): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            $automation = Automation::where('uid', $uid)
                ->where('customer_id', $user->customer_id)
                ->first();

            // Check if automation exists
            if (! $automation) {
                return response()->json(['success' => false, 'error' => 'Automation not found'], 404);
            }

            // Authorize
            if (! $user->can('disable', $automation)) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }

            // Disable the automation
            $automation->status = 'inactive';
            $automation->save();

            return response()->json([
                'success' => true,
                'message' => 'Automation disabled successfully',
                'data' => [
                    'uid' => $automation->uid,
                    'name' => $automation->name,
                    'status' => $automation->status,
                ],
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
