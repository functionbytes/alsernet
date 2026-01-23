<?php

namespace Modules\HelpdeskChat\Http\Controllers\Callcenter;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\HelpdeskChat\Events\NewMessageEvent;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;

class MessageController extends Controller
{
    /**
     * Store a new message in conversation.
     *
     * POST /callcenter/helpdesk/conversation/{conversation}/messages
     */
    public function store(Request $request, string $conversation): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'content_type' => 'nullable|in:text,file,image',
            'private' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
            ], 422);
        }

        // Find conversation
        $conversationModel = Conversation::findOrFail($conversation);

        // Verify agent is assigned to this conversation or is admin
        if ($conversationModel->assignee_id !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'error' => 'You are not assigned to this conversation',
            ], 403);
        }

        try {
            // Create message
            $message = ConversationMessage::create([
                'conversation_id' => $conversationModel->id,
                'inbox_id' => $conversationModel->inbox_id,
                'account_id' => $conversationModel->account_id,
                'sender_id' => auth()->id(),
                'sender_type' => User::class,
                'message_type' => 'outgoing',
                'content' => $request->content,
                'content_type' => $request->input('content_type', 'text'),
                'private' => $request->input('private', false),
            ]);

            // Update conversation timestamp
            $conversationModel->update([
                'last_activity_at' => now(),
                'last_message_at' => now(),
            ]);

            // Broadcast to customer and other agents
            event(new NewMessageEvent($message));

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'message_id' => $message->id,
                    'created_at' => $message->created_at->toIso8601String(),
                    'content' => $message->content,
                    'message_type' => $message->message_type,
                    'sender' => [
                        'id' => auth()->id(),
                        'name' => auth()->user()->name,
                    ],
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
