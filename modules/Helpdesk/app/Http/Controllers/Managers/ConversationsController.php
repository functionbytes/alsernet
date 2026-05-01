<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Exceptions\WhatsAppHsmException;
use Modules\Helpdesk\Filters\ConversationFilter;
use Modules\Helpdesk\Http\Requests\ConversationAjaxActionRequest;
use Modules\Helpdesk\Http\Requests\MarkSpamRequest;
use Modules\Helpdesk\Http\Requests\RequestAiSuggestionsRequest;
use Modules\Helpdesk\Http\Requests\SaveDraftRequest;
use Modules\Helpdesk\Http\Requests\SendEmailFromConversationRequest;
use Modules\Helpdesk\Http\Requests\SendHsmRequest;
use Modules\Helpdesk\Http\Requests\SnoozeConversationRequest;
use Modules\Helpdesk\Http\Requests\StoreContactItemRequest;
use Modules\Helpdesk\Http\Requests\StoreConversationMessageRequest;
use Modules\Helpdesk\Http\Requests\StoreConversationRequest;
use Modules\Helpdesk\Http\Requests\StoreLocationItemRequest;
use Modules\Helpdesk\Http\Requests\StoreScheduledMessageRequest;
use Modules\Helpdesk\Http\Requests\UpdateConversationRequest;
use Modules\Helpdesk\Http\Requests\UploadAttachmentRequest;
use Modules\Helpdesk\Jobs\SendScheduledMessageJob;
use Modules\Helpdesk\Jobs\UnsnoozeConversationJob;
use Modules\Helpdesk\Mail\CustomerOutboundEmail;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\ConversationView;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Services\ConversationMessageService;
use Modules\Helpdesk\Services\ConversationTagService;
use Modules\Helpdesk\Services\CsatService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\Helpdesk\Services\WhatsAppHsmService;

class ConversationsController extends Controller
{
    public function __construct(private ConversationTagService $tagService)
    {
        $this->middleware('can:helpdesk.conversations.view')->only(['index', 'show', 'listJson', 'kanban']);
        $this->middleware('can:helpdesk.conversations.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.conversations.update')->only([
            'edit', 'update', 'close', 'reopen', 'archive', 'unarchive',
            'storeMessage', 'snooze', 'togglePin', 'toggleMute',
            'sendEmail', 'sendHsm', 'merge', 'mergeCandidates',
            'saveDraft', 'storeScheduledMessage', 'aiSuggestions',
            'uploadAttachments', 'storeContact', 'storeLocation',
        ]);
        $this->middleware('can:helpdesk.conversations.delete')->only(['destroy', 'restore', 'forceDelete', 'blockContact']);
        $this->middleware('can:helpdesk.conversations.update')->only(['markSpam']);
    }

    /**
     * Display a listing of conversations
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Conversation::class);

        $userId = auth()->id();

        $views = ConversationView::forUser($userId)->ordered()->get();

        $currentView = null;
        if ($request->has('viewId')) {
            $currentView = $views->firstWhere('id', $request->viewId);
        }

        if (! $currentView) {
            $currentView = $views->firstWhere('is_default', true) ?? $views->first();
        }

        $filter = new ConversationFilter($request);

        $query = Conversation::query()
            ->with(['customer', 'status', 'assignee']);

        if ($currentView && ! empty($currentView->filters)) {
            $filter->applyViewFilters($query, $currentView->filters);
        }

        $filter->apply($query);

        // Quick-filter chips (unread / mine / urgent / vip)
        $query
            ->when(
                $request->boolean('unread'),
                fn ($q) => $q->whereDoesntHave(
                    'reads',
                    fn ($r) => $r->where('user_id', $userId)
                )
            )
            ->when(
                $request->boolean('mine'),
                fn ($q) => $q->where('assignee_id', $userId)
            )
            ->when(
                $request->boolean('urgent'),
                fn ($q) => $q->where('priority', 'urgent')
            )
            ->when(
                $request->boolean('vip'),
                fn ($q) => $q->whereHas(
                    'customer',
                    fn ($c) => $c->where('total_conversations', '>=', 5)
                )
            )
            ->when(
                $request->filled('tag'),
                fn ($q) => $q->whereHas(
                    'conversationTags',
                    fn ($t) => $t->where('helpdesk_conversation_tags.id', $request->integer('tag'))
                )
            );

        $this->applySortOrder($query, $request->input('sort', 'newest'), $userId);

        $conversations = $query->paginate(50)->appends($request->query());
        $statuses = ConversationStatus::active()->ordered()->get();
        $groups = Group::orderBy('name')->get();

        $totalConversations = Conversation::query()->count();

        $inboxTags = cache()->remember(
            'helpdesk:inbox:tags',
            60,
            fn () => ConversationTag::query()
                ->where('is_active', true)
                ->withCount('conversations')
                ->orderBy('name')
                ->get()
        );

        $statusbarMetrics = cache()->remember(
            'helpdesk:inbox:statusbar',
            30,
            function () {
                $today = now()->startOfDay();

                $activeChannels = (int) Conversation::query()
                    ->whereNotNull('channel')
                    ->where('channel', '!=', '')
                    ->distinct()
                    ->count('channel');

                $resolvedToday = (int) Conversation::query()
                    ->where('closed_at', '>=', $today)
                    ->count();

                $firstResponseAvg = (int) Conversation::query()
                    ->whereNotNull('first_response_at')
                    ->whereDate('first_response_at', $today)
                    ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) as avg_sec')
                    ->value('avg_sec');

                $agentsOnline = (int) DB::table('sessions')
                    ->whereNotNull('user_id')
                    ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
                    ->distinct('user_id')
                    ->count('user_id');

                return [
                    'active_channels' => $activeChannels,
                    'agents_online' => $agentsOnline,
                    'sla_avg_seconds' => $firstResponseAvg,
                    'resolved_today' => $resolvedToday,
                ];
            }
        );

        // Counters reales para el sidebar (cacheados 60s para no penalizar la lista)
        $sidebarCounters = cache()->remember(
            'helpdesk:inbox:counters:'.($userId ?? 'guest'),
            60,
            function () use ($userId) {
                $base = Conversation::query();

                return [
                    // Sin leer: abiertas y sin asignar (heurística sin read receipts)
                    'unread' => (clone $base)
                        ->whereHas('status', fn ($q) => $q->where('is_open', true))
                        ->whereNull('assignee_id')
                        ->count(),
                    'mine' => $userId ? (clone $base)->where('assignee_id', $userId)->count() : 0,
                    'urgent' => (clone $base)->where('priority', 'urgent')->count(),
                    'pending' => (clone $base)
                        ->whereHas('status', fn ($q) => $q->where('name', 'Esperando'))
                        ->count(),
                    'archived' => (clone $base)->where('is_archived', true)->count(),
                    'whatsapp' => (clone $base)->where('channel', 'whatsapp')->count(),
                    'facebook' => (clone $base)->where('channel', 'facebook')->count(),
                    'instagram' => (clone $base)->where('channel', 'instagram')->count(),
                    'email' => (clone $base)->where('channel', 'email')->count(),
                    'widget' => (clone $base)->whereIn('channel', ['widget', 'web'])->count(),
                    'vip' => (clone $base)
                        ->whereHas('customer', fn ($c) => $c->where('total_conversations', '>=', 5))
                        ->count(),
                ];
            }
        );

        $selectedId = $request->integer('selected') ?: $conversations->first()?->id;

        $selectedConversation = $selectedId
            ? Conversation::query()
                ->with(['customer', 'status', 'assignee', 'conversationTags', 'items' => fn ($q) => $q->orderBy('created_at')])
                ->find($selectedId)
            : null;

        $inboxGroups = $conversations->getCollection()
            ->groupBy(function (Conversation $c): string {
                $at = $c->last_message_at ?? $c->updated_at ?? $c->created_at;

                if (! $at) {
                    return 'older';
                }

                if ($at->isToday()) {
                    return 'today';
                }

                if ($at->isYesterday()) {
                    return 'yesterday';
                }

                return $at->isAfter(now()->subDays(7)) ? 'week' : 'older';
            })
            ->map(fn ($items) => $items->map(fn ($c) => $c->toInboxArray($selectedId))->values()->all());

        $composerDraft = $selectedConversation?->drafts()
            ->where('user_id', auth()->id())
            ->first();

        $agents = User::query()
            ->select(['id', 'firstname', 'lastname', 'email'])
            ->orderBy('firstname')
            ->get();

        return view('helpdesk::managers.inbox.index', [
            'conversations' => $conversations,
            'inboxGroups' => $inboxGroups,
            'statuses' => $statuses,
            'groups' => $groups,
            'agents' => $agents,
            'views' => $views,
            'currentView' => $currentView,
            'filters' => $request->only(['status', 'assignee', 'group', 'priority', 'search', 'archived', 'channel', 'unread', 'mine', 'urgent', 'vip', 'tag']),
            'totalConversations' => $totalConversations,
            'sidebarCounters' => $sidebarCounters,
            'inboxTags' => $inboxTags,
            'statusbarMetrics' => $statusbarMetrics,
            'selectedConversationId' => $selectedId,
            'selectedConversation' => $selectedConversation,
            'composerDraft' => $composerDraft,
        ]);
    }

    /**
     * Return a JSON payload with rendered HTML for the inbox list + counts.
     * Used by the frontend AJAX refresh (SPA-style navigation without full reload).
     */
    public function listJson(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $views = ConversationView::forUser($userId)->ordered()->get();

        $currentView = null;
        if ($request->has('viewId')) {
            $currentView = $views->firstWhere('id', $request->viewId);
        }

        if (! $currentView) {
            $currentView = $views->firstWhere('is_default', true) ?? $views->first();
        }

        $filter = new ConversationFilter($request);

        $query = Conversation::query()
            ->with(['customer', 'status', 'assignee']);

        if ($currentView && ! empty($currentView->filters)) {
            $filter->applyViewFilters($query, $currentView->filters);
        }

        $filter->apply($query);

        $query
            ->when(
                $request->boolean('unread'),
                fn ($q) => $q->whereDoesntHave(
                    'reads',
                    fn ($r) => $r->where('user_id', $userId)
                )
            )
            ->when(
                $request->boolean('mine'),
                fn ($q) => $q->where('assignee_id', $userId)
            )
            ->when(
                $request->boolean('urgent'),
                fn ($q) => $q->where('priority', 'urgent')
            )
            ->when(
                $request->boolean('vip'),
                fn ($q) => $q->whereHas(
                    'customer',
                    fn ($c) => $c->where('total_conversations', '>=', 5)
                )
            );

        $this->applySortOrder($query, $request->input('sort', 'newest'), $userId);

        $conversations = $query->paginate(50)->appends($request->query());

        $selectedId = $request->integer('selected') ?: $conversations->first()?->id;

        $inboxGroups = $conversations->getCollection()
            ->groupBy(function (Conversation $c): string {
                $at = $c->last_message_at ?? $c->updated_at ?? $c->created_at;

                if (! $at) {
                    return 'older';
                }

                if ($at->isToday()) {
                    return 'today';
                }

                if ($at->isYesterday()) {
                    return 'yesterday';
                }

                return $at->isAfter(now()->subDays(7)) ? 'week' : 'older';
            })
            ->map(fn ($items) => $items->map(fn ($c) => $c->toInboxArray($selectedId))->values()->all());

        $html = view('helpdesk::managers.inbox.partials.list', [
            'inboxGroups' => $inboxGroups,
            'totalConversations' => Conversation::query()->count(),
            'selectedConversationId' => $selectedId,
        ])->render();

        $counts = [
            'total' => $conversations->total(),
            'unread' => (int) Conversation::query()
                ->whereDoesntHave('reads', fn ($r) => $r->where('user_id', $userId))
                ->count(),
            'mine' => (int) Conversation::query()
                ->where('assignee_id', $userId)
                ->count(),
            'urgent' => (int) Conversation::query()
                ->where('priority', 'urgent')
                ->count(),
            'channels' => Conversation::query()
                ->selectRaw('channel, COUNT(*) as cnt')
                ->groupBy('channel')
                ->pluck('cnt', 'channel')
                ->toArray(),
        ];

        return response()->json([
            'success' => true,
            'html' => $html,
            'counts' => $counts,
        ]);
    }

    /**
     * Show the form for creating a new conversation
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Conversation::class);

        $customer = null;
        if ($request->has('customer')) {
            $customer = Customer::findOrFail($request->customer);
        }

        $statuses = ConversationStatus::open()->ordered()->get();

        return view('helpdesk::managers.conversations.create', [
            'customer' => $customer,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Store a newly created conversation
     */
    public function store(StoreConversationRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $conversation = Conversation::create([
            'customer_id' => $validated['customer_id'],
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
        ]);
        $conversation->status_id = $validated['status_id'];
        $conversation->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_created'),
                'conversation' => [
                    'id' => $conversation->id,
                    'url' => route('manager.helpdesk.conversations.index', ['selected' => $conversation->id]),
                ],
            ], 201);
        }

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', __('helpdesk::helpdesk.messages.conversation_created'));
    }

    /**
     * Send a real email to the customer and persist it as a ConversationItem.
     */
    public function sendEmail(SendEmailFromConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validated();

        $conversation->loadMissing('customer');
        $customer = $conversation->customer;

        if (! filled($customer?->email)) {
            return response()->json([
                'success' => false,
                'message' => 'El contacto no tiene dirección de email.',
            ], 422);
        }

        $cc = $validated['cc'] ?? [];
        $bcc = $validated['bcc'] ?? [];
        $bodyHtml = nl2br(e($validated['body']));

        $item = DB::transaction(function () use ($conversation, $validated, $cc, $bcc, $bodyHtml): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'email_sent',
                'body' => $validated['body'],
                'html_body' => $bodyHtml,
                'is_internal' => false,
                'metadata' => [
                    'subject' => $validated['subject'],
                    'cc' => $cc,
                    'bcc' => $bcc,
                ],
            ]);
        });

        Mail::to($customer->email)
            ->cc($cc)
            ->bcc($bcc)
            ->queue(new CustomerOutboundEmail(
                conversation: $conversation,
                subject: $validated['subject'],
                bodyHtml: $bodyHtml,
                bodyPlain: $validated['body'],
                cc: $cc,
                bcc: $bcc,
            ));

        return response()->json([
            'success' => true,
            'message' => 'Email enviado correctamente.',
            'item' => [
                'id' => $item->id,
                'body' => $item->body,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => auth()->user()?->name,
                'is_outgoing' => true,
            ],
        ], 201);
    }

    /**
     * Send a WhatsApp HSM template to the customer.
     */
    public function sendHsm(SendHsmRequest $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validated();

        $conversation->loadMissing('customer');

        try {
            $result = app(WhatsAppHsmService::class)->send(
                conversation: $conversation,
                templateName: $validated['template_name'],
                variables: $validated['variables'] ?? [],
            );
        } catch (WhatsAppHsmException $e) {
            Log::warning('HSM send failed', array_merge(['message' => $e->getMessage()], $e->context()));

            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar la plantilla: '.$e->getMessage(),
            ], 422);
        }

        $waMessageId = $result['id'] ?? null;
        $isMocked = $result['mocked'] ?? false;

        $item = DB::transaction(function () use ($conversation, $validated, $waMessageId, $isMocked): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'message',
                'body' => "[Plantilla HSM: {$validated['template_name']}]",
                'is_internal' => false,
                'metadata' => [
                    'hsm_template' => $validated['template_name'],
                    'hsm_variables' => $validated['variables'] ?? [],
                    'wa_message_id' => $waMessageId,
                    'mocked' => $isMocked,
                ],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => $isMocked
                ? 'Plantilla registrada (WhatsApp no configurado — modo desarrollo).'
                : 'Plantilla enviada por WhatsApp.',
            'item' => [
                'id' => $item->id,
                'body' => $item->body,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => auth()->user()?->name,
                'is_outgoing' => true,
                'wa_message_id' => $waMessageId,
            ],
        ], 201);
    }

    /**
     * Display the specified conversation
     */
    public function show(Conversation $conversation, Request $request): View
    {
        $this->authorize('view', $conversation);

        $conversation->load(['customer', 'status', 'assignee', 'items.user', 'items.author', 'conversationTags']);

        $statuses = ConversationStatus::orderBy('order')->get();

        // Get available tags for the modal
        $availableTags = ConversationTag::active()->orderBy('name')->get();

        // Get views for sidebar (same as index)
        $views = ConversationView::query()
            ->forUser(Auth::id())
            ->ordered()
            ->get();

        // Get current view if specified
        $currentView = null;
        if ($request->filled('viewId')) {
            $currentView = ConversationView::find($request->viewId);
        }

        // Get groups for sidebar
        $groups = Group::with('users')->get();

        // Get conversations list (same as index)
        $conversationsQuery = Conversation::query()
            ->with(['customer', 'status', 'assignee'])
            ->withCount('messages');

        // Apply view filters if specified
        if ($currentView) {
            $filters = $currentView->filters ?? [];
            // Apply filters logic here (simplified for now)
        }

        // Apply group filter if specified
        if ($request->filled('group')) {
            $conversationsQuery->whereHas('assignee.groups', function ($q) use ($request) {
                $q->where('id', $request->group);
            });
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $conversationsQuery->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        // Get paginated conversations
        $conversations = $conversationsQuery->latest('updated_at')->paginate(20);

        return view('helpdesk::managers.conversations.show', [
            'conversation' => $conversation,
            'statuses' => $statuses,
            'availableTags' => $availableTags,
            'views' => $views,
            'currentView' => $currentView,
            'groups' => $groups,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Show the form for editing the conversation
     */
    public function edit(Conversation $conversation): View
    {
        $this->authorize('update', $conversation);

        $conversation->load(['customer', 'status', 'assignee']);
        $statuses = ConversationStatus::orderBy('order')->get();
        $agents = User::select(['id', 'firstname', 'lastname', 'email'])->orderBy('firstname')->get();

        return view('helpdesk::managers.conversations.edit', [
            'conversation' => $conversation,
            'statuses' => $statuses,
            'agents' => $agents,
        ]);
    }

    /**
     * Update the specified conversation
     */
    public function update(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        // AJAX path: lightweight partial updates (tag/priority/assignee) — handled by ConversationAjaxActionRequest
        if ($request->ajax() || $request->wantsJson()) {
            return $this->handleAjaxUpdate($conversation);
        }

        // Regular form submission — validate via UpdateConversationRequest
        $validated = app(UpdateConversationRequest::class)->validated();

        $conversation->update([
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
        ]);

        $conversation->status_id = $validated['status_id'];

        if (isset($validated['assignee_id'])) {
            if ($validated['assignee_id'] && $validated['assignee_id'] !== $conversation->assignee_id) {
                $conversation->assignTo($validated['assignee_id']);
            } elseif (! $validated['assignee_id']) {
                $conversation->assignee_id = null;
                $conversation->assigned_at = null;
            }
        }

        if (isset($validated['is_archived'])) {
            $conversation->is_archived = $validated['is_archived'];
        }

        $conversation->save();

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', __('helpdesk::helpdesk.messages.conversation_updated'));
    }

    /**
     * Handle AJAX partial updates (tag toggle, priority, assignee).
     */
    private function handleAjaxUpdate(Conversation $conversation): JsonResponse
    {
        $request = app(ConversationAjaxActionRequest::class);
        $action = $request->input('action');

        if ($action === 'add_tag') {
            $tag = $this->tagService->addTag($conversation, (int) $request->validated()['tag_id']);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.tag_added'),
                'tag' => $tag,
            ]);
        }

        if ($action === 'remove_tag') {
            $this->tagService->removeTag($conversation, (int) $request->validated()['tag_id']);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.tag_removed'),
            ]);
        }

        if ($request->has('tag_ids')) {
            $tagIds = array_map('intval', $request->validated()['tag_ids'] ?? []);
            $conversation->conversationTags()->sync($tagIds);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.tags_updated'),
            ]);
        }

        if ($request->has('priority')) {
            $conversation->update(['priority' => $request->validated()['priority']]);

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.priority_updated'),
            ]);
        }

        if ($request->has('status_id')) {
            $conversation->status_id = $request->validated()['status_id'];
            $conversation->save();

            $status = $conversation->status()->first();

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_updated'),
                'status' => [
                    'id' => $status?->id,
                    'name' => $status?->name,
                    'color' => $status?->color,
                ],
            ]);
        }

        if ($request->has('assignee_id')) {
            $assigneeId = $request->validated()['assignee_id'] ?? null;

            if ($assigneeId) {
                $conversation->assignTo($assigneeId);
            } else {
                $conversation->assignee_id = null;
                $conversation->assigned_at = null;
                $conversation->save();
            }

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.assignment_updated'),
            ]);
        }

        if ($request->has('group_id')) {
            $conversation->group_id = $request->input('group_id') ?: null;
            $conversation->save();

            return response()->json([
                'success' => true,
                'message' => 'Conversación movida al equipo correctamente.',
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified conversation (soft delete)
     */
    public function destroy(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $conversation);

        $conversation->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_deleted'),
            ]);
        }

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_deleted'));
    }

    /**
     * Restore a soft-deleted conversation
     */
    public function restore($id): RedirectResponse
    {
        $conversation = Conversation::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $conversation);

        $conversation->restore();

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_restored'));
    }

    /**
     * Permanently delete a conversation
     */
    public function forceDelete($id): RedirectResponse
    {
        $conversation = Conversation::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $conversation);

        $conversation->forceDelete();

        return redirect()->route('manager.helpdesk.conversations.index')
            ->with('success', __('helpdesk::helpdesk.messages.conversation_force_deleted'));
    }

    /**
     * Close a conversation
     */
    public function close(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->close();

        // Fire CSAT survey (best effort — never block close on a survey error).
        try {
            app(CsatService::class)->dispatchForConversation($conversation);
        } catch (\Throwable $e) {
            \Log::warning('CSAT dispatch failed on close', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_closed'),
            ]);
        }

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_closed'));
    }

    /**
     * Reopen a conversation
     */
    public function reopen(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->reopen();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_reopened'),
            ]);
        }

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_reopened'));
    }

    /**
     * Archive a conversation
     */
    public function archive(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->archive();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_archived'),
            ]);
        }

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_archived'));
    }

    /**
     * Unarchive a conversation
     */
    public function unarchive(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->unarchive();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.conversation_unarchived'),
            ]);
        }

        return redirect()->back()
            ->with('success', __('helpdesk::helpdesk.messages.conversation_unarchived'));
    }

    /**
     * List merge candidates: other conversations from the same customer.
     */
    public function mergeCandidates(Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $candidates = Conversation::query()
            ->where('customer_id', $conversation->customer_id)
            ->where('id', '!=', $conversation->id)
            ->whereNull('deleted_at')
            ->with(['customer', 'status'])
            ->latest('last_message_at')
            ->limit(10)
            ->get()
            ->map(fn (Conversation $c) => [
                'id' => $c->id,
                'subject' => $c->subject ?? '#'.$c->id,
                'channel' => $c->channel ?? 'widget',
                'channel_icon' => $c->channel_info['icon'],
                'preview' => mb_strimwidth(strip_tags((string) ($c->getLatestMessage()?->body ?? '')), 0, 80, '…'),
                'time' => $c->last_message_at?->diffForHumans() ?? $c->created_at?->diffForHumans() ?? '—',
                'status' => $c->status?->name,
            ]);

        return response()->json(['success' => true, 'data' => $candidates]);
    }

    /**
     * Merge current conversation into a target: moves items then soft-deletes current.
     */
    public function merge(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $targetId = (int) $request->input('target_id');

        if ($targetId === $conversation->id) {
            return response()->json(['success' => false, 'message' => 'No puedes fusionar una conversación consigo misma.'], 422);
        }

        $target = Conversation::findOrFail($targetId);

        if ($target->customer_id !== $conversation->customer_id) {
            return response()->json(['success' => false, 'message' => 'Las conversaciones no pertenecen al mismo contacto.'], 422);
        }

        \DB::transaction(function () use ($conversation, $target): void {
            $conversation->items()->update(['conversation_id' => $target->id]);
            $conversation->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Conversaciones fusionadas correctamente.',
            'target_id' => $target->id,
        ]);
    }

    /**
     * Store a new message in a conversation
     */
    public function storeMessage(StoreConversationMessageRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        [$item, $successMessage] = app(ConversationMessageService::class)->store($conversation, [
            'body' => $validated['body'],
            'is_internal' => $request->boolean('is_internal'),
            'attachments' => $request->file('attachments', []),
            'action' => $request->input('action'),
            'reply_to_id' => $validated['reply_to_id'] ?? null,
        ]);

        $replyMeta = null;
        if (! empty($validated['reply_to_id'])) {
            $replyItem = ConversationItem::find($validated['reply_to_id']);
            if ($replyItem) {
                $replyAuthor = $replyItem->user?->name
                    ?? $replyItem->author?->name
                    ?? $conversation->customer?->name
                    ?? 'Cliente';
                $replyMeta = [
                    'id' => $replyItem->id,
                    'author' => $replyAuthor,
                    'body' => Str::limit($replyItem->body, 80),
                ];
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'item' => [
                    'id' => $item?->id,
                    'body' => $item?->body,
                    'is_internal' => (bool) $item?->is_internal,
                    'created_at' => $item?->created_at?->toIso8601String(),
                    'time' => $item?->created_at?->format('H:i'),
                    'author' => $item?->user?->firstname
                        ? trim($item->user->firstname.' '.$item->user->lastname)
                        : (auth()->user()?->firstname
                            ? trim(auth()->user()->firstname.' '.auth()->user()->lastname)
                            : null),
                    'is_outgoing' => true,
                    'reply_to' => $replyMeta,
                ],
            ], 201);
        }

        return redirect()->route('manager.helpdesk.conversations.show', $conversation)
            ->with('success', $successMessage);
    }

    /**
     * Sirve un attachment como descarga forzada con Content-Disposition.
     * Usado por el lightbox y el menú "Descargar" del bubble.
     *
     * Recibe ?url= con la URL completa o solo el path /storage/... y devuelve
     * el archivo bajo la disk public con el filename original.
     */
    public function downloadAttachment(Request $request)
    {
        $url = trim((string) $request->input('url', ''));
        if ($url === '') {
            abort(400, 'URL requerida');
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            abort(400, 'URL inválida');
        }

        // Solo permitimos paths bajo /storage/helpdesk/ por seguridad
        if (! preg_match('#^/storage/(helpdesk/.+)$#', $path, $m)) {
            abort(403, 'Path no permitido');
        }
        $relPath = $m[1];

        $disk = Storage::disk('public');
        if (! $disk->exists($relPath)) {
            abort(404, 'Archivo no encontrado');
        }

        $filename = basename($relPath);

        return $disk->download($relPath, $filename, [
            'Content-Type' => $disk->mimeType($relPath) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Snooze a conversation until a given datetime.
     */
    public function snooze(SnoozeConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $until = $request->date('until');

        $conversation->update([
            'snoozed_until' => $until,
            'snoozed_by' => auth()->id(),
        ]);

        UnsnoozeConversationJob::dispatch($conversation)->delay($until);

        return response()->json([
            'success' => true,
            'message' => 'Conversación pospuesta hasta '.$until->format('d/m/Y H:i').'.',
            'snoozed_until' => $until->toIso8601String(),
        ]);
    }

    /**
     * Toggle pin for the current user on this conversation.
     */
    public function togglePin(Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $userId = auth()->id();

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $userId)
            ->where('conversation_id', $conversation->id)
            ->first();

        $pinned = false;

        if ($meta) {
            $pinned = $meta->pinned_at === null;
            DB::connection('helpdesk')
                ->table('helpdesk_user_conversation_meta')
                ->where('id', $meta->id)
                ->update(['pinned_at' => $pinned ? now() : null, 'updated_at' => now()]);
        } else {
            DB::connection('helpdesk')
                ->table('helpdesk_user_conversation_meta')
                ->insert([
                    'user_id' => $userId,
                    'conversation_id' => $conversation->id,
                    'pinned_at' => now(),
                    'muted_until' => null,
                    'blocked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $pinned = true;
        }

        return response()->json([
            'success' => true,
            'pinned' => $pinned,
            'message' => $pinned ? 'Conversación fijada.' : 'Conversación desfijada.',
        ]);
    }

    /**
     * Toggle mute for the current user on this conversation.
     */
    public function toggleMute(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $userId = auth()->id();
        $until = $request->filled('until') ? $request->date('until') : now()->addDays(7);

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $userId)
            ->where('conversation_id', $conversation->id)
            ->first();

        $muted = false;

        if ($meta) {
            $muted = $meta->muted_until === null || now()->greaterThan($meta->muted_until);
            DB::connection('helpdesk')
                ->table('helpdesk_user_conversation_meta')
                ->where('id', $meta->id)
                ->update([
                    'muted_until' => $muted ? $until : null,
                    'updated_at' => now(),
                ]);
        } else {
            DB::connection('helpdesk')
                ->table('helpdesk_user_conversation_meta')
                ->insert([
                    'user_id' => $userId,
                    'conversation_id' => $conversation->id,
                    'pinned_at' => null,
                    'muted_until' => $until,
                    'blocked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $muted = true;
        }

        return response()->json([
            'success' => true,
            'muted' => $muted,
            'message' => $muted ? 'Conversación silenciada.' : 'Conversación reactivada.',
        ]);
    }

    /**
     * Block the contact associated with a conversation.
     */
    public function blockContact(Conversation $conversation): JsonResponse
    {
        $this->authorize('delete', $conversation);

        $customer = Customer::find($conversation->customer_id);

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Contacto no encontrado.'], 404);
        }

        $customer->update([
            'banned_at' => now(),
            'ban_reason' => 'blocked from inbox',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contacto bloqueado correctamente.',
        ]);
    }

    /**
     * Save or delete the current user's composer draft for a conversation.
     * If body is empty, the draft is deleted.
     */
    public function saveDraft(SaveDraftRequest $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validated();
        $body = trim($validated['body'] ?? '');

        if ($body === '') {
            $conversation->drafts()->where('user_id', auth()->id())->delete();

            return response()->json(['success' => true, 'message' => 'Borrador eliminado.']);
        }

        $conversation->drafts()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'body' => $body,
                'is_internal' => $request->boolean('is_internal'),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Borrador guardado.']);
    }

    /**
     * Schedule a message to be sent at a future datetime.
     */
    public function storeScheduledMessage(StoreScheduledMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validated();
        $scheduledAt = Carbon::parse($validated['scheduled_at']);

        $item = DB::transaction(function () use ($conversation, $validated, $scheduledAt): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'message',
                'body' => $validated['body'],
                'is_internal' => $request->boolean('is_internal'),
                'scheduled_at' => $scheduledAt,
                'scheduled_by' => auth()->id(),
            ]);
        });

        SendScheduledMessageJob::dispatch($item)->delay($scheduledAt);

        return response()->json([
            'success' => true,
            'message' => 'Mensaje programado para '.$scheduledAt->format('d/m/Y H:i').'.',
            'item' => [
                'id' => $item->id,
                'body' => $item->body,
                'scheduled_at' => $scheduledAt->toIso8601String(),
                'scheduled_at_formatted' => $scheduledAt->format('d/m/Y H:i'),
            ],
        ], 201);
    }

    /**
     * Return AI-generated reply suggestions for a conversation.
     * TODO: integrar OpenAI/Claude API
     */
    public function aiSuggestions(RequestAiSuggestionsRequest $request, Conversation $conversation): JsonResponse
    {
        $suggestions = [
            [
                'id' => 1,
                'type' => 'greeting',
                'text' => '¡Hola! Gracias por contactarnos. Estoy aquí para ayudarte. ¿En qué puedo asistirte hoy?',
            ],
            [
                'id' => 2,
                'type' => 'clarification',
                'text' => 'Para poder ayudarte mejor, ¿podrías darme más detalles sobre tu consulta? Cualquier información adicional nos permitirá resolverlo más rápido.',
            ],
            [
                'id' => 3,
                'type' => 'closing',
                'text' => 'Ha sido un placer atenderte. Si tienes alguna otra duda, no dudes en escribirnos. ¡Que tengas un excelente día!',
            ],
        ];

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Upload one or more file attachments to a conversation.
     * Images > 1 MB are compressed (max 1920px, JPEG 80%) before saving.
     */
    public function uploadAttachments(UploadAttachmentRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->loadMissing('customer');
        $customerId = $conversation->customer_id;
        $dateFolder = now()->format('Y-m-d');

        $attachments = [];

        foreach ($request->file('files') as $file) {
            $mime = $file->getMimeType() ?? 'application/octet-stream';
            $folder = "helpdesk/customers/{$customerId}/conversations/{$conversation->id}/{$dateFolder}";
            $disk = Storage::disk('public');

            if ($this->shouldCompressImage($mime, $file->getSize())) {
                [$filename, $storedMime, $storedSize] = $this->compressAndStoreImage($file, $folder, $disk);
            } else {
                $filename = $file->hashName();
                $disk->putFileAs($folder, $file, $filename);
                $storedMime = $mime;
                $storedSize = $file->getSize();
            }

            $attachments[] = [
                'url' => $disk->url("{$folder}/{$filename}"),
                'name' => $file->getClientOriginalName(),
                'size' => $storedSize,
                'mime' => $storedMime,
                'mime_type' => $storedMime,
                'type' => $this->resolveAttachmentType($storedMime),
                'path' => "{$folder}/{$filename}",
            ];
        }

        $item = DB::transaction(function () use ($conversation, $attachments): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'message',
                'body' => '',
                'is_internal' => false,
                // Store rich objects (matches widget format) so the thread renderer
                // has {url, name, size, mime_type} for image/audio/video/document.
                'attachment_urls' => $attachments,
                'metadata' => ['attachments' => $attachments],
            ]);
        });

        // Forward each attachment to the customer through the channel API.
        $outbound = app(OutboundMessageService::class);
        if ($outbound->supports($conversation)) {
            $externalIds = [];
            foreach ($attachments as $att) {
                try {
                    $absUrl = $this->absoluteUrl((string) $att['url']);
                    $externalId = $outbound->sendAttachment(
                        $conversation,
                        (string) ($att['type'] ?? 'file'),
                        $absUrl,
                        null,
                        $att['name'] ?? null,
                    );
                    if ($externalId) {
                        $externalIds[] = $externalId;
                    }
                } catch (\Throwable $e) {
                    \Log::error('uploadAttachments: outbound send failed', [
                        'conversation_id' => $conversation->id,
                        'channel' => $conversation->channel,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            if ($externalIds) {
                $item->external_id = $externalIds[0];
                $item->save();
            }
        }

        broadcast(new ConversationMessageCreated($item, false))->toOthers();

        return response()->json([
            'success' => true,
            'message' => count($attachments).' archivo(s) adjunto(s) correctamente.',
            'item' => [
                'id' => $item->id,
                'body' => $item->body,
                'type' => $item->type,
                'attachment_urls' => $attachments,
                'attachments' => $attachments,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => auth()->user()?->name,
                'is_outgoing' => true,
            ],
        ], 201);
    }

    private function absoluteUrl(string $url): string
    {
        $base = rtrim(config('helpdesk.public_url') ?? config('app.url'), '/');
        $appUrl = rtrim((string) config('app.url'), '/');

        if (preg_match('#^https?://#i', $url)) {
            if ($appUrl && $base !== $appUrl && str_starts_with($url, $appUrl.'/')) {
                return $base.substr($url, strlen($appUrl));
            }

            return $url;
        }

        return $base.'/'.ltrim($url, '/');
    }

    /**
     * Store a contact card as a conversation item.
     */
    public function storeContact(StoreContactItemRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();

        $item = DB::transaction(function () use ($conversation, $validated): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'contact',
                'body' => $validated['name'],
                'is_internal' => false,
                'metadata' => [
                    'name' => $validated['name'],
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                ],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Contacto compartido.',
            'item' => [
                'id' => $item->id,
                'type' => 'contact',
                'metadata' => $item->metadata,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => auth()->user()?->name,
                'is_outgoing' => true,
            ],
        ], 201);
    }

    /**
     * Store a location pin as a conversation item.
     */
    public function storeLocation(StoreLocationItemRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();

        $item = DB::transaction(function () use ($conversation, $validated): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'location',
                'body' => $validated['address'] ?? "{$validated['lat']},{$validated['lng']}",
                'is_internal' => false,
                'metadata' => [
                    'lat' => $validated['lat'],
                    'lng' => $validated['lng'],
                    'address' => $validated['address'] ?? null,
                ],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Ubicación compartida.',
            'item' => [
                'id' => $item->id,
                'type' => 'location',
                'metadata' => $item->metadata,
                'is_internal' => false,
                'created_at' => $item->created_at?->toIso8601String(),
                'time' => $item->created_at?->format('H:i'),
                'author' => auth()->user()?->name,
                'is_outgoing' => true,
            ],
        ], 201);
    }

    /**
     * Mark a conversation as spam, archive it, and ban the customer.
     */
    public function markSpam(MarkSpamRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        DB::transaction(function () use ($conversation): void {
            $conversation->update([
                'is_spam' => true,
                'is_archived' => true,
            ]);

            $customer = Customer::find($conversation->customer_id);
            if ($customer) {
                $customer->update([
                    'banned_at' => now(),
                    'ban_reason' => 'spam',
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Conversación marcada como spam.',
        ]);
    }

    /**
     * Forward an attachment from one conversation to another.
     */
    public function forwardAttachment(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validate([
            'source_url' => 'required|url',
            'original_name' => 'nullable|string|max:255',
        ]);

        // Extract path from source URL — only allow our own storage
        $url = $validated['source_url'];
        $storageBase = config('app.url').'/storage/';
        $altBase = url('/storage/').'/';
        $path = null;

        foreach ([$storageBase, $altBase] as $base) {
            if (str_starts_with($url, $base)) {
                $path = substr($url, strlen($base));
                break;
            }
        }
        // Also accept raw path within helpdesk/customers/...
        if (! $path && preg_match('#/storage/(helpdesk/customers/.+)$#', $url, $m)) {
            $path = $m[1];
        }

        if (! $path || ! \Storage::disk('public')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Archivo no encontrado en almacenamiento.'], 404);
        }

        $customerId = $conversation->customer_id ?: 0;
        $convId = $conversation->id;
        $newPath = "helpdesk/customers/{$customerId}/conversations/{$convId}/".now()->format('Y-m-d').'/'.basename($path);

        \Storage::disk('public')->copy($path, $newPath);
        $newUrl = \Storage::disk('public')->url($newPath);

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'type' => 'message',
            'body' => 'Archivo reenviado: '.($validated['original_name'] ?? basename($path)),
            'attachment_urls' => [$newUrl],
            'is_internal' => false,
            'metadata' => ['forwarded_from' => $validated['source_url']],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Archivo reenviado correctamente.',
            'item' => [
                'id' => $item->id,
                'body' => $item->body,
                'attachment_urls' => $item->attachment_urls,
                'time' => $item->created_at->format('H:i'),
            ],
        ], 201);
    }

    /**
     * Apply sort order to the conversations query based on the sort param.
     *
     * Supported values: newest (default), oldest, priority, unassigned, unread
     */
    private function applySortOrder(Builder $query, string $sort, int $userId): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('last_message_at'),
            'priority' => $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')"),
            'unassigned' => $query->orderByRaw('assignee_id IS NOT NULL ASC')->orderBy('last_message_at', 'desc'),
            'unread' => $query->orderByRaw(
                '(SELECT COUNT(*) FROM helpdesk_conversation_reads WHERE conversation_id = helpdesk_conversations.id AND user_id = ?) = 0 DESC',
                [$userId]
            )->orderBy('last_message_at', 'desc'),
            default => $query->orderBy('last_message_at', 'desc'),
        };
    }

    /**
     * Whether an uploaded image should be compressed before storage.
     * GIFs are skipped (animation). PNGs with alpha are skipped (transparency).
     */
    private function shouldCompressImage(string $mime, int $bytes): bool
    {
        if (! str_starts_with($mime, 'image/')) {
            return false;
        }

        if ($bytes <= 1 * 1024 * 1024) {
            return false;
        }

        // Skip GIF (animation)
        if ($mime === 'image/gif') {
            return false;
        }

        return true;
    }

    /**
     * Compress an image: resize to max 1920px on the longest side, save as JPEG 80%.
     * PNG images with alpha channel are also saved as JPEG (alpha is not preserved).
     *
     * Returns [filename, mime, size] of the stored file.
     */
    private function compressAndStoreImage(UploadedFile $file, string $folder, Filesystem $disk): array
    {
        $originalBytes = $file->getSize();
        $manager = new ImageManager(new GdDriver);

        $image = $manager->read($file->getRealPath());

        // Skip PNG with alpha channel (preserve transparency)
        if ($file->getMimeType() === 'image/png') {
            $gd = $image->core()->native();
            if (imageistruecolor($gd) && imagecolorsforindex($gd, 0)['alpha'] > 0) {
                $filename = $file->hashName();
                $disk->putFileAs($folder, $file, $filename);

                return [$filename, 'image/png', $originalBytes];
            }
        }

        // Resize: max 1920px on the longest side, keep aspect ratio
        $image->scaleDown(width: 1920, height: 1920);

        $encoded = $image->toJpeg(quality: 80);
        $filename = pathinfo($file->hashName(), PATHINFO_FILENAME).'.jpg';
        $disk->put("{$folder}/{$filename}", $encoded->toString());

        $storedSize = $disk->size("{$folder}/{$filename}");
        $savedKb = round(($originalBytes - $storedSize) / 1024);

        Log::info('Helpdesk: image compressed', [
            'original_bytes' => $originalBytes,
            'stored_bytes' => $storedSize,
            'saved_kb' => $savedKb,
            'file' => $filename,
        ]);

        return [$filename, 'image/jpeg', $storedSize];
    }

    /**
     * Determine the semantic attachment type from a MIME type.
     */
    private function resolveAttachmentType(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default => 'document',
        };
    }

    /**
     * Kanban board view grouped by status
     */
    public function kanban(): View
    {
        $statuses = ConversationStatus::active()->ordered()->get();

        $conversations = Conversation::query()
            ->with(['customer', 'status'])
            ->orderByDesc('last_message_at')
            ->limit(200)
            ->get();

        $byStatus = $conversations->groupBy('status_id');

        return view('helpdesk::managers.inbox.kanban', compact('statuses', 'byStatus'));
    }
}
