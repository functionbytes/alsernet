<?php

namespace Modules\HelpdeskChat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\HelpdeskChat\Events\NewMessageEvent;
use Modules\HelpdeskChat\Models\Channels\Web;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Contacts\ContactInbox;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;
use Modules\HelpdeskChat\Services\AgentPresenceService;

class WidgetApiController extends Controller
{
    /**
     * Initialize a new conversation from widget.
     *
     * POST /api/widget/{websiteToken}/init
     */
    public function initConversation(Request $request, string $websiteToken): JsonResponse
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

        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Create or retrieve contact
            $contact = $this->findOrCreateContact($webWidget->account_id, $request->all());

            // Create or retrieve contact inbox
            $contactInbox = ContactInbox::firstOrCreate([
                'contact_id' => $contact->id,
                'inbox_id' => $inbox->id,
            ], [
                'source_id' => $contact->id,
            ]);

            // Create new conversation
            $conversation = Conversation::create([
                'account_id' => $webWidget->account_id,
                'inbox_id' => $inbox->id,
                'contact_id' => $contact->id,
                'contact_inbox_id' => $contactInbox->id,
                'status' => 'open',
                'language' => $request->input('language', 'en'),
                'last_activity_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
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
    public function sendMessage(Request $request, string $websiteToken): JsonResponse
    {
        // Validate websiteToken
        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            return response()->json(['error' => 'Invalid widget token'], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:helpdesk_conversations,id',
            'content' => 'required|string',
            'message_type' => 'nullable|in:incoming,outgoing',
            'attachments' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Find conversation and verify it belongs to this widget's inbox
            $conversation = Conversation::find($request->conversation_id);

            if ($conversation->inbox->channel_id !== $webWidget->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Create message
            $message = ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'inbox_id' => $conversation->inbox_id,
                'account_id' => $webWidget->account_id,
                'sender_id' => $conversation->contact_id,
                'sender_type' => Contact::class,
                'message_type' => $request->input('message_type', 'incoming'),
                'content' => $request->content,
                'content_type' => 'text',
                'content_attributes' => $request->input('attachments', []),
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

        try {
            // Find conversation and verify access
            $conversation = Conversation::find($conversationId);

            if (! $conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            if ($conversation->inbox->channel_id !== $webWidget->id) {
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

            // Check if any agents are actually online
            $presenceService = new AgentPresenceService;
            $agentsOnline = $presenceService->hasAvailableAgents($webWidget->account_id);
            $onlineCount = $presenceService->getAvailableAgentsCount($webWidget->account_id);

            $available = $isWithinBusinessHours && $agentsOnline;

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
     * Find or create contact based on provided data.
     */
    protected function findOrCreateContact(int $accountId, array $data): Contact
    {
        $query = Contact::where('account_id', $accountId);

        // Try to find by email first
        if (! empty($data['email'])) {
            $contact = $query->where('email', $data['email'])->first();
            if ($contact) {
                return $contact;
            }
        }

        // Try to find by phone
        if (! empty($data['phone_number'])) {
            $contact = $query->where('phone_number', $data['phone_number'])->first();
            if ($contact) {
                return $contact;
            }
        }

        // Create new contact
        return Contact::create([
            'account_id' => $accountId,
            'name' => $data['name'] ?? 'Anonymous',
            'email' => $data['email'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'last_activity_at' => now(),
        ]);
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
}
