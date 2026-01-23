<?php

namespace Modules\HelpdeskChat\Http\Controllers\Api;

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

class WidgetConversationController extends Controller
{
    /**
     * Create a new conversation from widget.
     *
     * POST /lc/api/conversation
     */
    public function store(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'website_token' => 'required|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Find web widget
            $webWidget = Web::where('website_token', $request->website_token)->first();

            if (! $webWidget) {
                return response()->json(['error' => 'Invalid widget token'], 404);
            }

            $inbox = $webWidget->inbox;

            if (! $inbox) {
                return response()->json(['error' => 'Widget not configured'], 500);
            }

            DB::beginTransaction();

            // Create or retrieve contact
            $contact = $this->findOrCreateContact($webWidget->account_id, $request->all());

            // Create contact inbox
            $contactInbox = ContactInbox::firstOrCreate([
                'contact_id' => $contact->id,
                'inbox_id' => $inbox->id,
            ], [
                'source_id' => $contact->id,
            ]);

            // Create conversation
            $conversation = Conversation::create([
                'account_id' => $webWidget->account_id,
                'inbox_id' => $inbox->id,
                'contact_id' => $contact->id,
                'contact_inbox_id' => $contactInbox->id,
                'status' => 'open',
                'language' => $request->input('language', 'en'),
                'last_activity_at' => now(),
            ]);

            // Create initial message if provided
            if ($request->filled('message')) {
                $message = ConversationMessage::create([
                    'conversation_id' => $conversation->id,
                    'inbox_id' => $inbox->id,
                    'account_id' => $webWidget->account_id,
                    'sender_id' => $contact->id,
                    'sender_type' => Contact::class,
                    'message_type' => 'incoming',
                    'content' => $request->message,
                    'content_type' => 'text',
                ]);

                $conversation->update(['last_message_at' => now()]);

                // Broadcast to agents via WebSocket
                event(new NewMessageEvent($message));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Conversation created successfully',
                'data' => [
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

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
        try {
            $conversation = Conversation::with(['contact', 'assignee', 'inbox'])->find($id);

            if (! $conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $conversation->id,
                    'status' => $conversation->status,
                    'created_at' => $conversation->created_at->toIso8601String(),
                    'contact' => [
                        'id' => $conversation->contact->id,
                        'name' => $conversation->contact->name,
                        'email' => $conversation->contact->email,
                    ],
                    'agent' => $conversation->assignee ? [
                        'id' => $conversation->assignee->id,
                        'name' => $conversation->assignee->name,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Send a message in conversation.
     *
     * POST /lc/api/conversation/{id}/messages
     */
    public function sendMessage(Request $request, string $id): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'content_type' => 'nullable|in:text,file,image',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $conversation = Conversation::find($id);

            if (! $conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            // Create message
            $message = ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'inbox_id' => $conversation->inbox_id,
                'account_id' => $conversation->account_id,
                'sender_id' => $conversation->contact_id,
                'sender_type' => Contact::class,
                'message_type' => 'incoming',
                'content' => $request->content,
                'content_type' => $request->input('content_type', 'text'),
            ]);

            // Update conversation
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
     * Get conversation messages.
     *
     * GET /lc/api/conversation/{id}/messages
     */
    public function getMessages(Request $request, string $id): JsonResponse
    {
        try {
            $conversation = Conversation::find($id);

            if (! $conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            $messages = ConversationMessage::where('conversation_id', $id)
                ->with('sender')
                ->orderBy('created_at', 'asc')
                ->limit(100)
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'content' => $message->content,
                        'content_type' => $message->content_type,
                        'message_type' => $message->message_type,
                        'created_at' => $message->created_at->toIso8601String(),
                        'sender' => [
                            'type' => class_basename($message->sender_type),
                            'id' => $message->sender_id,
                            'name' => $message->sender->name ?? 'Unknown',
                        ],
                    ];
                });

            // Mark outgoing messages as read (messages from agents to customer)
            ConversationMessage::where('conversation_id', $id)
                ->where('message_type', 'outgoing')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $id,
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
     * Close conversation from widget.
     *
     * POST /lc/api/conversation/{id}/close
     */
    public function close(Request $request, string $id): JsonResponse
    {
        try {
            $conversation = Conversation::find($id);

            if (! $conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            $conversation->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            // Trigger CSAT survey if enabled for the inbox
            $inbox = $conversation->inbox;
            if ($inbox && $inbox->csat_survey_enabled) {
                // Create CSAT survey record
                $csatSurvey = \Modules\HelpdeskChat\Models\CsatSurvey::create([
                    'account_id' => $conversation->account_id,
                    'conversation_id' => $conversation->id,
                    'contact_id' => $conversation->contact_id,
                ]);

                // Mark as sent and send email
                $csatSurvey->markAsSent();

                // Send CSAT survey email to contact
                \Illuminate\Support\Facades\Mail::to($conversation->contact->email)
                    ->send(new \Modules\HelpdeskChat\Mail\CsatSurveyMail($csatSurvey));
            }

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

    /**
     * Find or create contact.
     */
    protected function findOrCreateContact(int $accountId, array $data): Contact
    {
        $query = Contact::where('account_id', $accountId);

        if (! empty($data['email'])) {
            $contact = $query->where('email', $data['email'])->first();
            if ($contact) {
                return $contact;
            }
        }

        if (! empty($data['phone_number'])) {
            $contact = Contact::where('account_id', $accountId)
                ->where('phone_number', $data['phone_number'])
                ->first();
            if ($contact) {
                return $contact;
            }
        }

        return Contact::create([
            'account_id' => $accountId,
            'name' => $data['name'] ?? 'Anonymous',
            'email' => $data['email'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'last_activity_at' => now(),
        ]);
    }
}
