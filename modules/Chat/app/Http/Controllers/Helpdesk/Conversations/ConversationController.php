<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Conversations;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Http\Requests\Conversations\StoreConversationRequest;
use Modules\Chat\Http\Requests\Conversations\UpdateConversationRequest;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationLabel;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationPriority;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;
use Modules\Chat\Models\Inbox\Inbox;
use Modules\Chat\Models\Macro;
use Modules\Chat\Models\Teams\Team;
use Modules\Chat\Services\AttachmentConfigService;
use Modules\Chat\Services\Conversations\ConversationIndexService;

class ConversationController extends Controller
{
    public function __construct(
        private readonly AttachmentConfigService $attachmentConfig,
        private readonly ConversationIndexService $indexService,
    ) {}

    /**
     * Display a listing of conversation.
     */
    public function index(Request $request): View
    {
        $accountId = $request->user()->account_id ?? abort(403, 'No account assigned');

        $query = Conversation::forAccount($accountId)->withCommonRelations();

        if ($request->filled('status')) {
            $query->withStatusSlug($request->status);
        }

        if ($request->filled('priority')) {
            $query->withPrioritySlug($request->priority);
        }

        if ($request->filled('inbox')) {
            $query->byInbox($request->inbox);
        }

        if ($request->filled('assignee')) {
            $query->assignedTo($request->assignee);
        }

        if ($request->filled('team')) {
            $query->byTeam($request->team);
        }

        if ($request->filled('labels')) {
            $labels = is_array($request->labels) ? $request->labels : [$request->labels];
            $query->where(function ($q) use ($labels) {
                foreach ($labels as $label) {
                    $q->orWhere('cached_label_list', 'like', '%'.$label.'%');
                }
            });
        }

        $query->createdBetween($request->date_from, $request->date_to);
        $query->lastActivityBetween($request->last_activity_from, $request->last_activity_to);

        if ($request->filled('search')) {
            $query->textSearch($request->search);
        }

        $allowedSortFields = ['created_at', 'last_activity_at', 'customer_name'];
        $sortField = in_array($request->query('sort_by'), $allowedSortFields, true)
            ? $request->query('sort_by')
            : 'last_activity_at';

        $sortDirection = $request->query('sort_direction') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortField, $sortDirection)->orderBy('id', 'desc');

        $paginator = $query->cursorPaginate(25);
        $conversations = $paginator->getCollection();
        $nextCursor = $paginator->nextCursor()?->encode();
        $hasMore = $paginator->hasMorePages();

        $activeFilters = [
            'status' => $request->query('status'),
            'priority' => $request->query('priority'),
            'inbox' => $request->query('inbox'),
            'assignee' => $request->input('assignee'),
            'team' => $request->input('team'),
        ];

        $commonData = $this->indexService->buildIndexData($accountId, auth()->id(), auth()->user()->full_name);

        return view('Chat::chats.conversation.index', array_merge(
            compact('conversations', 'nextCursor', 'hasMore', 'activeFilters'),
            $commonData,
        ));
    }

    /**
     * Display the specified conversation.
     */
    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $accountId = $conversation->account_id;

        $conversation->load([
            'customer',
            'inbox.channel',
            'assignee',
            'status',
            'priority',
            'messages.sender',
            'messages.media',
            'slaTracking.slaPolicy',
            'slaPolicy',
            'customerInbox',
        ]);

        $previousConversations = [];
        if ($conversation->customer) {
            $previousConversations = $conversation->customer->conversations()
                ->where('id', '!=', $conversation->id)
                ->with('inbox')
                ->latest('last_activity_at')
                ->limit(5)
                ->get();
        }

        $macros = [];
        if (auth()->user()->account) {
            $macros = auth()->user()->account->macros()
                ->where(function ($q) {
                    $q->where('visibility', Macro::VISIBILITY_GLOBAL)
                        ->orWhere('created_by_id', auth()->id());
                })
                ->orderBy('name')
                ->get();
        }

        $users = User::whereHas('chatAccounts', function ($query) use ($accountId) {
            $query->where('account_id', $accountId);
        })
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();

        $labelsByTitle = [];
        if (auth()->user()->account) {
            $labelsByTitle = auth()->user()->account->labels()->get()->keyBy('name');
        }

        $teams = Team::forAccount($accountId)
            ->orderBy('name')
            ->get();

        $statuses = ConversationStatus::forAccount($accountId)
            ->active()
            ->ordered()
            ->get();

        $priorities = ConversationPriority::forAccount($accountId)
            ->active()
            ->ordered()
            ->get();

        $attachmentConfig = $this->attachmentConfig->getConfig();
        $mimeIconMap = $this->attachmentConfig->getMimeIconMap();
        $acceptAttribute = $this->attachmentConfig->getAcceptAttribute();

        $attachEnabled = $attachmentConfig['enabled'] ?? true;
        $acceptAttr = $acceptAttribute;
        $attachMaxMb = $attachmentConfig['maxFileSizeMb'] ?? 5;
        $attachMaxCount = $attachmentConfig['maxAttachments'] ?? 10;
        $attachTypes = $attachmentConfig['allowedTypes'] ?? [];

        $viewData = compact(
            'conversation',
            'previousConversations',
            'macros',
            'users',
            'labelsByTitle',
            'teams',
            'statuses',
            'priorities',
            'attachmentConfig',
            'mimeIconMap',
            'acceptAttribute',
            'attachEnabled',
            'acceptAttr',
            'attachMaxMb',
            'attachMaxCount',
            'attachTypes'
        );

        if ($request->ajax() || $request->wantsJson()) {
            return view('Chat::chats.conversation.partials.detail', $viewData);
        }

        return view('Chat::chats.conversation.show', $viewData);
    }

    /**
     * Store a new conversation.
     */
    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $customer = Customer::findOrFail($request->input('customer_id'));
        $inbox = Inbox::findOrFail($request->input('inbox'));

        CustomerInbox::firstOrCreate([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
        ], [
            'source_id' => 'manual',
        ]);

        $accountId = auth()->user()->account_id ?? abort(403, 'No account assigned');
        $openStatus = ConversationStatus::where('slug', 'open')->first();
        $mediumPriority = ConversationPriority::where('slug', 'medium')->first();

        $conversation = Conversation::create([
            'account_id' => $accountId,
            'inbox_id' => $inbox->id,
            'customer_id' => $customer->id,
            'status_id' => $openStatus?->id,
            'assignee_id' => auth()->id(),
            'priority_id' => $mediumPriority?->id,
            'last_activity_at' => now(),
        ]);

        if (! empty($request->input('initial_message'))) {
            $conversation->messages()->create([
                'account_id' => auth()->user()->account_id ?? abort(403, 'No account assigned'),
                'inbox_id' => $inbox->id,
                'sender_id' => auth()->id(),
                'sender_type' => User::class,
                'message_type' => 'outgoing',
                'content_type' => 'text',
                'content' => $request->input('initial_message'),
                'private' => false,
            ]);
        }

        return redirect()->route('chat.conversations.show', $conversation)
            ->with('success', '¡Conversación creada exitosamente!');
    }

    /**
     * Update the conversation.
     */
    public function update(UpdateConversationRequest $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('update', $conversation);

        $conversation->update($request->validated());

        return back()->with('success', 'Conversation updated successfully!');
    }

    /**
     * Delete the conversation.
     */
    public function destroy(Conversation $conversation): RedirectResponse
    {
        $this->authorize('delete', $conversation);

        $conversation->delete();

        return redirect()->route('chat.conversations.index')
            ->with('success', 'Conversación eliminada correctamente.');
    }

    /**
     * Show conversation assigned to me.
     */
    public function mine(Request $request): View
    {
        return $this->filteredIndex($request, 'mine', fn ($q, $r) => $q->assignedTo($r->user()->id));
    }

    /**
     * Show unassigned conversation.
     */
    public function unassigned(Request $request): View
    {
        return $this->filteredIndex($request, 'unassigned', fn ($q) => $q->unassigned());
    }

    /**
     * Show conversation where I'm mentioned.
     */
    public function mentions(Request $request): View
    {
        return $this->filteredIndex($request, 'mentions', fn ($q, $r) => $q->mentioning($r->user()->id, $r->user()->full_name));
    }

    /**
     * Show unattended conversation (no agent has replied yet).
     */
    public function unattended(Request $request): View
    {
        return $this->filteredIndex($request, 'unattended', fn ($q) => $q->unattended());
    }

    /**
     * Show conversation by inbox/channel.
     */
    public function byInbox(Request $request, $inboxId): View
    {
        $accountId = $request->user()->account_id ?? abort(403, 'No account assigned');
        $inbox = Inbox::forAccount($accountId)->findOrFail($inboxId);

        return $this->filteredIndex($request, 'inbox', fn ($q) => $q->byInbox($inboxId), compact('inbox'));
    }

    /**
     * Show conversation by team.
     */
    public function byTeam(Request $request, $teamId): View
    {
        $accountId = $request->user()->account_id ?? abort(403, 'No account assigned');
        $team = Team::forAccount($accountId)->findOrFail($teamId);

        return $this->filteredIndex($request, 'team', fn ($q) => $q->byTeam($teamId), compact('team'));
    }

    /**
     * Show conversation by label.
     */
    public function byLabel(Request $request, $labelTitle): View
    {
        $accountId = $request->user()->account_id ?? abort(403, 'No account assigned');
        $label = ConversationLabel::forAccount($accountId)->where('name', $labelTitle)->firstOrFail();

        return $this->filteredIndex($request, 'label', fn ($q) => $q->withLabelInCachedList($labelTitle), compact('label'));
    }

    /**
     * Search contacts (API endpoint).
     */
    public function searchContacts(Request $request): JsonResponse
    {
        $searchTerm = $request->query('q', '');

        if (strlen($searchTerm) < 2) {
            return response()->json([]);
        }

        $accountId = auth()->user()->account_id ?? abort(403, 'No account assigned');

        $contacts = Customer::forAccount($accountId)
            ->search($searchTerm)
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone_number']);

        return response()->json($contacts);
    }

    /**
     * Search conversation and messages.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $accountId = auth()->user()->account_id ?? abort(403, 'No account assigned');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $conversationsQuery = Conversation::forAccount($accountId);

        if (is_numeric($query)) {
            $conversationsQuery->where('id', $query);
        } else {
            $conversationsQuery->textSearch($query);
        }

        $conversations = $conversationsQuery
            ->with([
                'customer',
                'inbox.channel',
                'assignee',
                'status',
                'messages' => fn ($q) => $q->latest()->limit(1),
            ])
            ->latest('last_activity_at')
            ->limit(20)
            ->get();

        $messageQuery = ConversationMessage::query()
            ->whereIn('conversation_id', $conversations->pluck('id'));

        if (strlen($query) >= 3) {
            $messageQuery->whereRaw('MATCH(content) AGAINST(? IN BOOLEAN MODE)', [$query.'*']);
        } else {
            $messageQuery->where('content', 'like', '%'.$query.'%');
        }

        $conversationIdsWithMatchingMessages = $messageQuery
            ->pluck('conversation_id')
            ->flip();

        $results = $conversations->map(function ($conversation) use ($conversationIdsWithMatchingMessages) {
            $lastMessage = $conversation->messages->first();
            $matchInMessage = $conversationIdsWithMatchingMessages->has($conversation->id);

            return [
                'id' => $conversation->id,
                'customer' => [
                    'name' => $conversation->customer->name,
                    'email' => $conversation->customer->email,
                    'phone' => $conversation->customer->phone_number,
                ],
                'inbox' => [
                    'name' => $conversation->inbox->name,
                    'type' => $conversation->inbox->channel_type,
                ],
                'status' => $conversation->status?->slug ?? $conversation->status,
                'assignee' => $conversation->assignee?->name,
                'last_message' => $lastMessage ? [
                    'content' => Str::limit($lastMessage->content, 100),
                    'created_at' => $lastMessage->created_at->diffForHumans(),
                ] : null,
                'last_activity_at' => $conversation->last_activity_at->diffForHumans(),
                'match_type' => $matchInMessage ? 'message' : 'customer',
                'url' => route('chat.conversations.show', $conversation),
            ];
        });

        return response()->json($results);
    }

    /**
     * Get conversation data for template variable replacement.
     */
    public function getVariableData(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return response()->json([
            'customer' => [
                'name' => $conversation->customer?->name ?? '',
                'email' => $conversation->customer?->email ?? '',
                'phone' => $conversation->customer?->phone_number ?? '',
                'city' => $conversation->customer?->city ?? '',
                'country' => $conversation->customer?->country ?? '',
                'id' => $conversation->customer?->id ?? '',
            ],
            'contact' => [
                'name' => $conversation->customer?->name ?? '',
                'email' => $conversation->customer?->email ?? '',
                'phone' => $conversation->customer?->phone_number ?? '',
                'city' => $conversation->customer?->city ?? '',
                'country' => $conversation->customer?->country ?? '',
                'id' => $conversation->customer?->id ?? '',
            ],
            'agent' => [
                'name' => trim(($conversation->assignee?->firstname ?? '').' '.($conversation->assignee?->lastname ?? '')) ?: 'Agent',
                'firstname' => $conversation->assignee?->firstname ?? '',
                'lastname' => $conversation->assignee?->lastname ?? '',
                'email' => $conversation->assignee?->email ?? '',
            ],
            'conversation' => [
                'id' => $conversation->id,
                'reference' => '#'.$conversation->id,
                'status' => $conversation->status?->name ?? '',
            ],
            'account' => [
                'name' => $conversation->account?->name ?? '',
                'id' => $conversation->account?->id ?? '',
            ],
            'system' => [
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'datetime' => now()->format('Y-m-d H:i:s'),
                'year' => now()->format('Y'),
            ],
        ]);
    }

    /**
     * Load more conversations for cursor-based pagination (AJAX).
     * Accepts ?cursor=xxx&filter=mine|unassigned|mentions|unattended|inbox|team|label
     * plus optional context ids (inbox_id, team_id, label).
     */
    public function loadMore(Request $request): JsonResponse
    {
        $accountId = $request->user()->account_id ?? abort(403, 'No account assigned');
        $filter = $request->query('filter', 'all');

        $query = Conversation::forAccount($accountId)
            ->withCommonRelations()
            ->orderBy('last_activity_at', 'desc')
            ->orderBy('id', 'desc');

        match ($filter) {
            'mine' => $query->assignedTo($request->user()->id),
            'unassigned' => $query->unassigned(),
            'mentions' => $query->mentioning($request->user()->id, $request->user()->full_name),
            'unattended' => $query->unattended(),
            'inbox' => $query->byInbox((int) $request->query('inbox_id')),
            'team' => $query->byTeam((int) $request->query('team_id')),
            'label' => $query->withLabelInCachedList((string) $request->query('label')),
            default => null,
        };

        $paginator = $query->cursorPaginate(25);

        $items = $paginator->getCollection()->map(function (Conversation $c) {
            $lastMessage = $c->messages->first();

            return [
                'id' => $c->id,
                'customer_name' => $c->customer?->name,
                'last_activity_at' => $c->last_activity_at?->format('h:i a') ?? 'N/A',
                'last_message' => $lastMessage ? strip_tags((string) $lastMessage->content) : 'Sin mensajes',
                'priority_slug' => $c->priority?->slug,
                'priority_name' => $c->priority?->name,
                'channel_display_name' => $c->inbox?->channel_display_name ?? 'Sin canal',
            ];
        });

        return response()->json([
            'items' => $items,
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
        ]);
    }

    /**
     * Shared filtered index for mine/unassigned/mentions/unattended/byInbox/byTeam/byLabel.
     */
    private function filteredIndex(Request $request, string $filter, callable $scope, array $extra = []): View
    {
        $accountId = $request->user()->account_id ?? abort(403, 'No account assigned');

        $query = Conversation::forAccount($accountId)
            ->withCommonRelations()
            ->orderBy('last_activity_at', 'desc')
            ->orderBy('id', 'desc');

        $scope($query, $request);

        $paginator = $query->cursorPaginate(25);
        $conversations = $paginator->getCollection();
        $nextCursor = $paginator->nextCursor()?->encode();
        $hasMore = $paginator->hasMorePages();

        $commonData = $this->getCommonIndexData($accountId);

        return view('Chat::chats.conversation.index', array_merge(
            compact('conversations', 'nextCursor', 'hasMore'),
            $commonData,
            $extra,
            ['filter' => $filter]
        ));
    }

    /**
     * Get common data needed by all index views.
     * Delegates to ConversationIndexService — single source of truth.
     */
    private function getCommonIndexData(int $accountId): array
    {
        return $this->indexService->buildIndexData($accountId, auth()->id(), auth()->user()->full_name);
    }
}
