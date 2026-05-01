<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Http\Requests\Widget\SendWidgetMessageRequest;
use Modules\Helpdesk\Http\Requests\Widget\StoreWidgetConversationRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\Widget\WidgetConversationService;

class WidgetConversationController extends Controller
{
    public function __construct(
        private readonly WidgetConversationService $service
    ) {}

    public function store(StoreWidgetConversationRequest $request): JsonResponse
    {
        try {
            $data = $this->service->createConversation(
                $request->validated('website_token'),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Conversation created successfully',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to create conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request, $id);

        if (! $customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = $this->service->getConversation((int) $id, $customerId);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to retrieve conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 404);
        }
    }

    public function getMessages(Request $request, string $id): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request, $id);

        if (! $customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = $this->service->getMessages((int) $id, $customerId);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to retrieve messages',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    public function sendMessage(SendWidgetMessageRequest $request, string $id): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request, $id);

        if (! $customerId) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Customer ID or email is required.',
            ], 401);
        }

        try {
            $payload = $request->validated();
            $files = $request->file('attachments');
            if ($files === null && $request->hasFile('attachments')) {
                $files = $request->allFiles()['attachments'] ?? [];
            }
            $payload['attachments'] = is_array($files) ? $files : ($files ? [$files] : []);

            $data = $this->service->sendMessage((int) $id, $customerId, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to send message',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    public function close(Request $request, string $id): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request, $id);

        if (! $customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $this->service->closeConversation((int) $id, $customerId);

            return response()->json([
                'success' => true,
                'message' => 'Conversation closed successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to close conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Accept either an explicit customer_id or a customer_email that matches
     * the conversation's owner.
     */
    private function resolveCustomerId(Request $request, string $conversationId): ?int
    {
        $customerId = (int) $request->input('customer_id');
        if ($customerId) {
            return $customerId;
        }

        $email = $request->input('customer_email');
        if (! $email) {
            return null;
        }

        $conversation = Conversation::find($conversationId);
        if (! $conversation) {
            return null;
        }

        return Customer::where('id', $conversation->customer_id)
            ->where('email', $email)
            ->value('id');
    }
}
