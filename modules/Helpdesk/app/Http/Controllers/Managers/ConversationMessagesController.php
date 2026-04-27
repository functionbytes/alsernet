<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\ConversationMessageRead;
use Modules\Helpdesk\Events\ConversationUserTyping;
use Modules\Helpdesk\Http\Requests\BroadcastTypingRequest;
use Modules\Helpdesk\Http\Requests\StoreConversationMessageRequest;
use Modules\Helpdesk\Models\CannedReply;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationRead;

class ConversationMessagesController extends Controller
{
    /**
     * Get all messages for a conversation (API endpoint)
     */
    public function index(Conversation $conversation)
    {
        $this->authorize('helpdesk.conversations.show');

        $items = $conversation->items()
            ->with(['author', 'user'])
            ->latest()
            ->paginate(50);

        return response()->json($items);
    }

    /**
     * Store a new message in a conversation
     */
    public function store(StoreConversationMessageRequest $request, Conversation $conversation)
    {
        $validated = $request->validated();

        // Handle attachments
        $attachmentUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('helpdesk/attachments', 'public');
                $attachmentUrls[] = Storage::url($path);
            }
        }

        // Create message item
        $item = $conversation->items()->create([
            'type' => 'message',
            'body' => $validated['body'],
            'html_body' => $validated['html_body'],
            'user_id' => auth()->id(),
            'is_internal' => $validated['is_internal'] ?? false,
            'attachment_urls' => $attachmentUrls,
        ]);

        // Load relationships
        $item->load(['user']);

        // Update conversation metadata
        $conversation->update([
            'last_message_at' => now(),
        ]);

        // If first response and not internal, update SLA
        if ($item->is_internal === false && ! $conversation->first_response_at) {
            $conversation->update(['first_response_at' => now()]);
        }

        // Broadcast event for real-time
        broadcast(new ConversationMessageCreated($item))->toOthers();

        return response()->json([
            'id' => $item->id,
            'conversation_id' => $conversation->id,
            'user_id' => $item->user_id,
            'type' => $item->type,
            'body' => $item->body,
            'html_body' => $item->html_body,
            'is_internal' => $item->is_internal,
            'created_at' => $item->created_at,
            'sender_name' => $item->user->name ?? 'Unknown',
            'sender_avatar' => $item->user?->getAvatarUrl(),
            'attachment_urls' => $item->attachment_urls,
        ], 201);
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(Request $request, ConversationItem $item)
    {
        $this->authorize('helpdesk.conversations.show');

        // Create or update read record
        ConversationRead::firstOrCreate([
            'conversation_item_id' => $item->id,
            'user_id' => auth()->id(),
        ]);

        // Broadcast event
        broadcast(new ConversationMessageRead($item, auth()->user()))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Broadcast typing indicator
     */
    public function broadcastTyping(BroadcastTypingRequest $request, Conversation $conversation)
    {
        $this->authorize('helpdesk.conversations.update');

        $validated = $request->validated();

        broadcast(new ConversationUserTyping(
            $conversation,
            auth()->user(),
            $validated['is_typing']
        ))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Delete a message (soft delete)
     */
    public function destroy(Request $request, ConversationItem $item)
    {
        $this->authorize('helpdesk.conversations.update');

        // Only allow deleting own messages or if admin
        if ($item->user_id !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get canned replies for current user
     */
    public function getCannedReplies()
    {
        $replies = CannedReply::query()
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                    ->orWhere('is_global', true);
            })
            ->latest('usage_count')
            ->get(['id', 'title', 'body', 'html_body']);

        return response()->json($replies);
    }
}
