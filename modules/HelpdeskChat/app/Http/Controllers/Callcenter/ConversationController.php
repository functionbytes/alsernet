<?php

namespace Modules\HelpdeskChat\Http\Controllers\Callcenter;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskChat\Events\ConversationAssignedEvent;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class ConversationController extends Controller
{
    /**
     * Display callcenter agent's conversations.
     *
     * GET /callcenter/helpdesk/conversation
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Conversation::with(['contact', 'inbox', 'assignee'])
            ->where('assignee_id', auth()->id())
            ->orderBy('last_activity_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by inbox
        if ($request->filled('inbox_id')) {
            $query->where('inbox_id', $request->inbox_id);
        }

        // Search by contact name or email
        if ($request->filled('search')) {
            $query->whereHas('contact', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $conversations = $query->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'conversations' => $conversations->map(function ($conversation) {
                        return [
                            'id' => $conversation->id,
                            'status' => $conversation->status,
                            'contact' => [
                                'id' => $conversation->contact->id,
                                'name' => $conversation->contact->name,
                                'email' => $conversation->contact->email,
                            ],
                            'inbox' => [
                                'id' => $conversation->inbox->id,
                                'name' => $conversation->inbox->name,
                            ],
                            'last_activity_at' => $conversation->last_activity_at->toIso8601String(),
                            'unread_count' => $conversation->unread_count ?? 0,
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $conversations->currentPage(),
                        'total' => $conversations->total(),
                        'per_page' => $conversations->perPage(),
                        'last_page' => $conversations->lastPage(),
                    ],
                ],
            ]);
        }

        return view('helpdeskChat::callcenter.conversations.index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Display specific conversation.
     *
     * GET /callcenter/helpdesk/conversation/{conversation}
     */
    public function show(Request $request, string $conversation): View|JsonResponse
    {
        $conversationModel = Conversation::with(['contact', 'inbox', 'assignee', 'contactInbox'])
            ->findOrFail($conversation);

        // Verify agent is assigned to this conversation or is admin
        if ($conversationModel->assignee_id !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            abort(403, 'You are not assigned to this conversation');
        }

        // Load messages with sender
        $messages = $conversationModel->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark incoming messages as read
        $conversationModel->messages()
            ->where('message_type', 'incoming')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => [
                        'id' => $conversationModel->id,
                        'status' => $conversationModel->status,
                        'created_at' => $conversationModel->created_at->toIso8601String(),
                        'last_activity_at' => $conversationModel->last_activity_at->toIso8601String(),
                        'contact' => [
                            'id' => $conversationModel->contact->id,
                            'name' => $conversationModel->contact->name,
                            'email' => $conversationModel->contact->email,
                            'phone_number' => $conversationModel->contact->phone_number,
                        ],
                        'inbox' => [
                            'id' => $conversationModel->inbox->id,
                            'name' => $conversationModel->inbox->name,
                        ],
                        'assignee' => $conversationModel->assignee ? [
                            'id' => $conversationModel->assignee->id,
                            'name' => $conversationModel->assignee->name,
                        ] : null,
                    ],
                    'messages' => $messages->map(function ($message) {
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
                    }),
                ],
            ]);
        }

        return view('helpdeskChat::callcenter.conversations.show', [
            'conversation' => $conversationModel,
            'messages' => $messages,
        ]);
    }

    /**
     * Close a conversation.
     *
     * POST /callcenter/helpdesk/conversation/{conversation}/close
     */
    public function close(Request $request, string $conversation): JsonResponse
    {
        $conversationModel = Conversation::findOrFail($conversation);

        // Verify agent owns this conversation or is admin
        if ($conversationModel->assignee_id !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'error' => 'You are not assigned to this conversation',
            ], 403);
        }

        // Update status to closed
        $conversationModel->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        // Trigger CSAT survey if enabled for the inbox
        $inbox = $conversationModel->inbox;
        if ($inbox && $inbox->csat_survey_enabled) {
            // Create CSAT survey record
            $csatSurvey = \Modules\HelpdeskChat\Models\CsatSurvey::create([
                'account_id' => $conversationModel->account_id,
                'conversation_id' => $conversationModel->id,
                'contact_id' => $conversationModel->contact_id,
            ]);

            // Mark as sent and send email
            $csatSurvey->markAsSent();

            // Send CSAT survey email to contact
            \Illuminate\Support\Facades\Mail::to($conversationModel->contact->email)
                ->send(new \Modules\HelpdeskChat\Mail\CsatSurveyMail($csatSurvey));
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation closed successfully',
        ]);
    }

    /**
     * Take ownership of unassigned conversation.
     *
     * POST /callcenter/helpdesk/conversation/{conversation}/take
     */
    public function take(Request $request, string $conversation): JsonResponse
    {
        $conversationModel = Conversation::findOrFail($conversation);

        // Check if conversation is already assigned
        if ($conversationModel->assignee_id && $conversationModel->assignee_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'error' => 'Conversation is already assigned to another agent',
            ], 422);
        }

        // Assign to current agent
        $conversationModel->update([
            'assignee_id' => auth()->id(),
            'status' => 'open',
        ]);

        // Reload to get fresh assignee relationship
        $conversationModel->load('assignee');

        // Broadcast assignment event
        event(new ConversationAssignedEvent($conversationModel));

        return response()->json([
            'success' => true,
            'message' => 'Conversation assigned to you',
            'data' => [
                'conversation_id' => $conversationModel->id,
                'assignee' => [
                    'id' => auth()->id(),
                    'name' => auth()->user()->name,
                ],
            ],
        ]);
    }
}
