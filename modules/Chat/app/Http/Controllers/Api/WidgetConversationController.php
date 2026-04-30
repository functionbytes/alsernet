<?php

namespace Modules\Chat\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Http\Requests\Widget\SendWidgetMessageRequest;
use Modules\Chat\Http\Requests\Widget\StoreWidgetConversationRequest;
use Modules\Chat\Services\Widget\WidgetConversationService;

class WidgetConversationController extends Controller
{
    public function __construct(
        private readonly WidgetConversationService $conversationService
    ) {}

    /**
     * Create a new conversation from widget.
     *
     * POST /lc/api/conversation
     */
    public function store(StoreWidgetConversationRequest $request): JsonResponse
    {
        try {
            $data = $this->conversationService->createConversation(
                $request->validated('website_token'),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Conversation created successfully',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get conversation details.
     *
     * GET /lc/api/conversation/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:chat_customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = $this->conversationService->getConversation($id, $request->customer_id);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === 'Conversation not found' ? 404 : 403;

            return response()->json([
                'error' => 'Failed to retrieve conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], $statusCode);
        }
    }

    /**
     * Send a message in conversation.
     *
     * POST /lc/api/conversation/{id}/messages
     */
    public function sendMessage(SendWidgetMessageRequest $request, string $id): JsonResponse
    {
        try {
            $data = $this->conversationService->sendMessage(
                $id,
                $request->validated('customer_id'),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to send message',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get conversation messages.
     *
     * GET /lc/api/conversation/{id}/messages
     */
    public function getMessages(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:chat_customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = $this->conversationService->getMessages($id, $request->customer_id);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve messages',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Close conversation from widget.
     *
     * POST /lc/api/conversation/{id}/close
     */
    public function close(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:chat_customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $this->conversationService->closeConversation($id, $request->customer_id);

            return response()->json([
                'success' => true,
                'message' => 'Conversation closed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to close conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }
}
