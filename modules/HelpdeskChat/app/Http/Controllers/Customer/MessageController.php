<?php

namespace Modules\HelpdeskChat\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\HelpdeskChat\Events\NewMessageEvent;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;

class MessageController extends Controller
{
    /**
     * Store a new message in conversation.
     *
     * POST /customer/helpdesk/conversation/{conversation}/messages
     */
    public function store(Request $request, string $conversation): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'content_type' => 'nullable|in:text,file,image',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
            ], 422);
        }

        // Get customer's contact record
        $contact = Contact::where('email', auth()->user()->email)
            ->orWhere('user_id', auth()->id())
            ->first();

        if (! $contact) {
            return response()->json([
                'success' => false,
                'error' => 'Contact not found',
            ], 404);
        }

        // Find conversation
        $conversationModel = Conversation::findOrFail($conversation);

        // Verify customer owns this conversation
        if ($conversationModel->contact_id !== $contact->id) {
            return response()->json([
                'success' => false,
                'error' => 'You do not have access to this conversation',
            ], 403);
        }

        // Validate conversation is not closed
        if ($conversationModel->status === 'closed') {
            return response()->json([
                'success' => false,
                'error' => 'Cannot send message to closed conversation',
            ], 422);
        }

        try {
            // Create message
            $message = ConversationMessage::create([
                'conversation_id' => $conversationModel->id,
                'inbox_id' => $conversationModel->inbox_id,
                'account_id' => $conversationModel->account_id,
                'sender_id' => $contact->id,
                'sender_type' => Contact::class,
                'message_type' => 'incoming',
                'content' => $request->content,
                'content_type' => $request->input('content_type', 'text'),
            ]);

            // Update conversation timestamp
            $conversationModel->update([
                'last_activity_at' => now(),
                'last_message_at' => now(),
            ]);

            // Broadcast to agents
            event(new NewMessageEvent($message));

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'message_id' => $message->id,
                    'created_at' => $message->created_at->toIso8601String(),
                    'content' => $message->content,
                    'message_type' => $message->message_type,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to send message',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }
}
