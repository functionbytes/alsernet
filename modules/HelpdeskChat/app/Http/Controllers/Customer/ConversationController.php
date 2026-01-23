<?php

namespace Modules\HelpdeskChat\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Modules\HelpdeskChat\Events\NewMessageEvent;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;

class ConversationController extends Controller
{
    /**
     * Display customer's conversations.
     *
     * GET /customer/helpdesk/conversation
     */
    public function index(Request $request): View|JsonResponse
    {
        // Get customer's contact record
        $contact = Contact::where('email', auth()->user()->email)
            ->orWhere('user_id', auth()->id())
            ->first();

        if (! $contact) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => ['conversations' => []],
                ]);
            }

            return view('helpdeskChat::customer.conversations.index', [
                'conversations' => collect([]),
            ]);
        }

        // Query conversations for this contact
        $query = Conversation::with(['inbox', 'assignee'])
            ->where('contact_id', $contact->id)
            ->orderBy('last_activity_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
                            'inbox' => [
                                'id' => $conversation->inbox->id,
                                'name' => $conversation->inbox->name,
                            ],
                            'last_activity_at' => $conversation->last_activity_at->toIso8601String(),
                            'unread_count' => $conversation->unread_count ?? 0,
                            'assignee' => $conversation->assignee ? [
                                'name' => $conversation->assignee->name,
                            ] : null,
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

        return view('helpdeskChat::customer.conversations.index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Show form to create new conversation.
     *
     * GET /customer/helpdesk/conversation/create
     */
    public function create(Request $request): View
    {
        // Get available inboxes for this account
        $inboxes = \Modules\HelpdeskChat\Models\Inbox::where('account_id', 1)
            ->get(['id', 'name', 'channel_type']);

        return view('helpdeskChat::customer.conversations.create', [
            'inboxes' => $inboxes,
        ]);
    }

    /**
     * Store a new conversation.
     *
     * POST /customer/helpdesk/conversation
     */
    public function store(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'inbox_id' => 'required|exists:inboxes,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
            ], 422);
        }

        try {
            // Get or create contact for current user
            $contact = Contact::where('email', auth()->user()->email)
                ->orWhere('user_id', auth()->id())
                ->first();

            if (! $contact) {
                $contact = Contact::create([
                    'account_id' => 1, // Default account
                    'user_id' => auth()->id(),
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                    'last_activity_at' => now(),
                ]);
            }

            DB::beginTransaction();

            // Create conversation
            $conversation = Conversation::create([
                'account_id' => $contact->account_id,
                'inbox_id' => $request->inbox_id,
                'contact_id' => $contact->id,
                'status' => 'open',
                'subject' => $request->input('subject'),
                'last_activity_at' => now(),
            ]);

            // Create initial message
            $message = ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'inbox_id' => $request->inbox_id,
                'account_id' => $contact->account_id,
                'sender_id' => $contact->id,
                'sender_type' => Contact::class,
                'message_type' => 'incoming',
                'content' => $request->message,
                'content_type' => 'text',
            ]);

            $conversation->update(['last_message_at' => now()]);

            // Broadcast to agents
            event(new NewMessageEvent($message));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Conversation created successfully',
                'data' => [
                    'conversation_id' => $conversation->id,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Failed to create conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display specific conversation.
     *
     * GET /customer/helpdesk/conversation/{conversation}
     */
    public function show(Request $request, string $conversation): View|JsonResponse
    {
        // Get customer's contact record
        $contact = Contact::where('email', auth()->user()->email)
            ->orWhere('user_id', auth()->id())
            ->first();

        if (! $contact) {
            abort(404, 'Contact not found');
        }

        // Load conversation
        $conversationModel = Conversation::with(['inbox', 'assignee'])
            ->findOrFail($conversation);

        // Verify customer owns this conversation
        if ($conversationModel->contact_id !== $contact->id) {
            abort(403, 'You do not have access to this conversation');
        }

        // Load messages with sender
        $messages = $conversationModel->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark outgoing messages as read (messages from agents)
        $conversationModel->messages()
            ->where('message_type', 'outgoing')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => [
                        'id' => $conversationModel->id,
                        'status' => $conversationModel->status,
                        'subject' => $conversationModel->subject,
                        'created_at' => $conversationModel->created_at->toIso8601String(),
                        'last_activity_at' => $conversationModel->last_activity_at->toIso8601String(),
                        'inbox' => [
                            'id' => $conversationModel->inbox->id,
                            'name' => $conversationModel->inbox->name,
                        ],
                        'assignee' => $conversationModel->assignee ? [
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

        return view('helpdeskChat::customer.conversations.show', [
            'conversation' => $conversationModel,
            'messages' => $messages,
        ]);
    }
}
