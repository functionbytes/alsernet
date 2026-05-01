<?php

namespace Modules\Chat\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Chat\Events\NewMessageEvent;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Http\Requests\Widget\InitWidgetConversationRequest;
use Modules\Chat\Http\Requests\Widget\SendWidgetMessageRequest;
use Modules\Chat\Models\Channels\Web;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;
use Modules\Chat\Services\AgentPresenceService;
use Modules\Chat\Services\Customers\CustomerLookupService;
use Modules\Helpdesk\Models\AgentSettings;

class WidgetApiController extends Controller
{
    public function __construct(
        protected CustomerLookupService $customerLookup
    ) {}

    /**
     * Initialize a new conversation from widget.
     *
     * POST /api/widget/{websiteToken}/init
     */
    public function initConversation(InitWidgetConversationRequest $request, string $websiteToken): JsonResponse
    {
        // Validate websiteToken
        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            return response()->json(['error' => 'Invalid widget token'], 404);
        }

        $inbox = $webWidget->inbox;

        if (! $inbox) {
            return response()->json(['error' => 'Widget not configured properly'], 500);
        }

        try {
            DB::beginTransaction();

            // Create or retrieve customer
            $customer = $this->customerLookup->findOrCreate($webWidget->account_id, $request->validated());

            // Create or retrieve customer inbox
            $customerInbox = CustomerInbox::firstOrCreate([
                'customer_id' => $customer->id,
                'inbox_id' => $inbox->id,
            ], [
                'source_id' => $customer->id,
            ]);

            // Create new conversation
            $openStatus = ConversationStatus::where('slug', 'open')->first();
            $conversation = Conversation::create([
                'account_id' => $webWidget->account_id,
                'inbox_id' => $inbox->id,
                'customer_id' => $customer->id,
                'status_id' => $openStatus?->id,
                'language' => $request->validated('language') ?? 'en',
                'last_activity_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'customer_id' => $customer->id,
                    'inbox_id' => $inbox->id,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to initialize conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Send a message from widget.
     *
     * POST /api/widget/{websiteToken}/send
     */
    public function sendMessage(SendWidgetMessageRequest $request, string $websiteToken): JsonResponse
    {
        // Validate websiteToken
        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            return response()->json(['error' => 'Invalid widget token'], 404);
        }

        try {
            // Find conversation and verify it belongs to this widget's inbox
            $conversation = Conversation::find($request->validated('conversation_id'));

            if ($conversation->inbox->channel_id !== $webWidget->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Verify customer ownership - customer must own the conversation
            if ((int) $conversation->customer_id !== (int) $request->input('customer_id')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Create message
            $message = ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'inbox_id' => $conversation->inbox_id,
                'account_id' => $webWidget->account_id,
                'sender_id' => $conversation->customer_id,
                'sender_type' => Customer::class,
                'message_type' => $request->validated('message_type') ?? 'incoming',
                'content' => $request->validated('content'),
                'content_type' => 'text',
                'content_attributes' => $request->validated('attachments') ?? [],
            ]);

            // Update conversation last activity
            $conversation->update([
                'last_activity_at' => now(),
                'last_message_at' => now(),
            ]);

            // Broadcast to agents via WebSocket
            event(new NewMessageEvent($message));

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'message_id' => $message->id,
                    'created_at' => $message->created_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to send message',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get messages for a conversation.
     *
     * GET /api/widget/{websiteToken}/messages/{conversationId}
     */
    public function getMessages(Request $request, string $websiteToken, string $conversationId): JsonResponse
    {
        // Validate websiteToken
        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            return response()->json(['error' => 'Invalid widget token'], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:chat_customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Find conversation and verify access
            $conversation = Conversation::find($conversationId);

            if (! $conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            if ($conversation->inbox->channel_id !== $webWidget->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Verify customer ownership - customer must own the conversation
            if ((int) $conversation->customer_id !== (int) $request->input('customer_id')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get messages
            $messages = ConversationMessage::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'asc')
                ->limit(100)
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'content' => $message->content,
                        'message_type' => $message->message_type,
                        'content_type' => $message->content_type,
                        'created_at' => $message->created_at->toIso8601String(),
                        'sender' => [
                            'type' => class_basename($message->sender_type),
                            'id' => $message->sender_id,
                        ],
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversationId,
                    'messages' => $messages,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve messages',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Check agent availability.
     *
     * GET /api/widget/{websiteToken}/availability
     */
    public function checkAvailability(Request $request, string $websiteToken): JsonResponse
    {
        // Validate websiteToken
        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            return response()->json(['error' => 'Invalid widget token'], 404);
        }

        try {
            $inbox = $webWidget->inbox;

            // Check business hours if enabled
            $isWithinBusinessHours = true;
            if ($inbox && $inbox->working_hours_enabled) {
                $isWithinBusinessHours = $this->isWithinBusinessHours($inbox->business_hours ?? []);
            }

            // Check if any agents are currently available by schedule
            $scheduleAvailable = $this->hasAnyScheduleAvailableAgent($webWidget->account_id);

            // Check physical presence (online via heartbeat)
            $presenceService = new AgentPresenceService;
            $onlineCount = $presenceService->getAvailableAgentsCount($webWidget->account_id);

            $available = $isWithinBusinessHours && $scheduleAvailable;

            $message = $available
                ? ($inbox->greeting_message ?? 'We\'re online and ready to help!')
                : ($inbox->out_of_office_message ?? 'We\'re currently offline. Leave us a message!');

            return response()->json([
                'success' => true,
                'data' => [
                    'available' => $available,
                    'message' => $message,
                    'within_business_hours' => $isWithinBusinessHours,
                    'agents_online' => $onlineCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check availability',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Check if current time is within business hours.
     */
    protected function isWithinBusinessHours(array $businessHours): bool
    {
        if (empty($businessHours)) {
            return true;
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('l')); // monday, tuesday, etc.
        $currentTime = $now->format('H:i');

        if (! isset($businessHours[$dayOfWeek]) || ! $businessHours[$dayOfWeek]['enabled']) {
            return false;
        }

        $dayHours = $businessHours[$dayOfWeek];

        return $currentTime >= $dayHours['start'] && $currentTime <= $dayHours['end'];
    }

    /**
     * Return true if at least one agent is accepting conversations right now
     * based on their configured schedule (accepts_conversations field).
     */
    protected function hasAnyScheduleAvailableAgent(int $accountId): bool
    {
        return AgentSettings::query()
            ->whereHas('user', fn ($q) => $q->where('account_id', $accountId)->whereNull('deleted_at'))
            ->get()
            ->contains(fn (AgentSettings $s) => $s->acceptsConversationsNow());
    }
}
