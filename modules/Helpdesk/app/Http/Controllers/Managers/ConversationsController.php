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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\ConversationTagAdded;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Exceptions\WhatsAppHsmException;
use Modules\Helpdesk\Filters\ConversationFilter;
use Modules\Helpdesk\Http\Requests\BulkApplyMacroRequest;
use Modules\Helpdesk\Http\Requests\ConversationAjaxActionRequest;
use Modules\Helpdesk\Http\Requests\ForwardAttachmentRequest;
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
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Campaigns\WhatsAppTemplate;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\ConversationView;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Models\Macro;
use Modules\Helpdesk\Services\ConversationMessageService;
use Modules\Helpdesk\Services\ConversationTagService;
use Modules\Helpdesk\Services\CsatService;
use Modules\Helpdesk\Services\LinkPreviewService;
use Modules\Helpdesk\Services\Macros\MacroExecutorService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\Helpdesk\Services\WhatsAppHsmService;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConversationsController extends Controller
{
    public function __construct(private ConversationTagService $tagService)
    {
        $this->middleware('can:helpdesk.conversations.view')->only(['index', 'show', 'listJson', 'kanban', 'emailLogIndex', 'emailLogShow']);
        $this->middleware('can:helpdesk.conversations.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.conversations.update')->only([
            'edit', 'update', 'close', 'reopen', 'archive', 'unarchive',
            'storeMessage', 'snooze', 'togglePin', 'toggleMute',
            'sendEmail', 'sendHsm', 'merge', 'mergeCandidates',
            'saveDraft', 'storeScheduledMessage', 'aiSuggestions',
            'uploadAttachments', 'storeContact', 'storeLocation', 'createTicket',
        ]);
        $this->middleware('can:helpdesk.conversations.delete')->only(['destroy', 'restore', 'forceDelete', 'blockContact']);
        $this->middleware('can:helpdesk.conversations.update')->only(['markSpam', 'sendCsatSurvey', 'bulkApplyMacro']);
        $this->middleware('can:helpdesk.macros.use')->only(['applyMacro', 'macrosForPicker']);
    }

    /**
     * Returns inbox IDs the current user may access, or null if unrestricted.
     * Users with helpdesk.manage see all inboxes; regular agents only see
     * the inboxes assigned via helpdesk_agent_inbox_capacity.
     *
     * A restricted agent with NO assigned inboxes returns an empty array (sees
     * nothing) — never null — so the listing does not leak conversations from
     * other inboxes.
     *
     * @return int[]|null null = no restriction (helpdesk.manage); array = allowed inbox IDs
     */
    private function getUserInboxIds(): ?array
    {
        $user = auth()->user();

        if ($user->can('helpdesk.manage')) {
            return null;
        }

        return AgentInboxCapacity::where('user_id', $user->id)->pluck('inbox_id')->all();
    }

    /**
     * Ensure the current agent may act on a conversation item: managers see
     * everything; other agents only items in their assigned inboxes. Prevents
     * cross-inbox access via a message id (reactToMessage/forwardMessage/messageInfo).
     */
    private function assertItemAccess(ConversationItem $item): void
    {
        $inboxIds = $this->getUserInboxIds();

        if ($inboxIds === null) {
            return; // helpdesk.manage → full access
        }

        $item->loadMissing('conversation');

        abort_unless(
            $item->conversation && in_array($item->conversation->inbox_id, $inboxIds, true),
            403
        );
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

        $userInboxIds = $this->getUserInboxIds();

        $query = Conversation::query()
            ->with([
                'customer', 'status', 'assignee', 'inbox', 'lastMessage',
                'reads' => fn ($q) => $q->where('user_id', auth()->id()),
            ])
            ->withCount(['items as incoming_messages_count' => fn ($q) => $q->where('type', 'message')->whereNull('user_id')])
            ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds));

        // Conversations the chatbot is handling stay out of the inbox until it
        // hands off to an agent. The "bot" view shows them (supervision) and a
        // search still finds them (history).
        $this->applyBotVisibility($query, $request);

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

        $totalConversations = Conversation::query()
            ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds))
            ->withoutActiveBot()
            ->count();

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

        // Counters reales para el sidebar (cacheados 60s, por usuario para aislar agentes)
        $sidebarCounters = cache()->remember(
            'helpdesk:inbox:counters:'.($userId ?? 'guest'),
            60,
            function () use ($userId, $userInboxIds) {
                $base = Conversation::query()
                    ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds));

                // Inbox counters exclude conversations the bot is still handling.
                $inbox = (clone $base)->withoutActiveBot();

                return [
                    // Sin leer: abiertas y sin asignar (heurística sin read receipts)
                    'unread' => (clone $inbox)
                        ->whereHas('status', fn ($q) => $q->where('is_open', true))
                        ->whereNull('assignee_id')
                        ->count(),
                    'mine' => $userId ? (clone $inbox)->where('assignee_id', $userId)->count() : 0,
                    'urgent' => (clone $inbox)->where('priority', 'urgent')->count(),
                    'pending' => (clone $inbox)
                        ->whereHas('status', fn ($q) => $q->where('name', 'Esperando'))
                        ->count(),
                    'archived' => (clone $inbox)->where('is_archived', true)->count(),
                    'whatsapp' => (clone $inbox)->where('channel', 'whatsapp')->count(),
                    'facebook' => (clone $inbox)->where('channel', 'facebook')->count(),
                    'instagram' => (clone $inbox)->where('channel', 'instagram')->count(),
                    'email' => (clone $inbox)->where('channel', 'email')->count(),
                    'web' => (clone $inbox)->whereIn('channel', ['web'])->count(),
                    'vip' => (clone $inbox)
                        ->whereHas('customer', fn ($c) => $c->where('total_conversations', '>=', 5))
                        ->count(),
                    // Conversaciones que el bot está atendiendo (supervisión).
                    'bot' => (clone $base)->handledByBot()->count(),
                ];
            }
        );

        // Per-inbox sidebar entries filtered by agent's assigned inboxes.
        // Managers (helpdesk.manage) see all inboxes; agents see only their own.
        $inboxCacheKey = $userInboxIds === null
            ? 'helpdesk:inbox:sidebar-list:all'
            : 'helpdesk:inbox:sidebar-list:user:'.auth()->id();

        $inboxes = cache()->remember(
            $inboxCacheKey,
            60,
            function () use ($userInboxIds) {
                return Inbox::query()
                    ->where('is_active', true)
                    ->when($userInboxIds !== null, fn ($q) => $q->whereIn('id', $userInboxIds))
                    ->orderBy('name')
                    ->get(['id', 'name', 'channel_type', 'color', 'icon'])
                    ->map(function (Inbox $inbox) use ($userInboxIds) {
                        $count = Conversation::query()
                            ->where('inbox_id', $inbox->id)
                            ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds))
                            ->count();
                        $inbox->setAttribute('conversations_count', $count);

                        return $inbox;
                    });
            }
        );

        $selectedId = $request->integer('selected') ?: $conversations->first()?->id;

        $selectedConversation = $selectedId
            ? Conversation::query()
                ->with(['customer', 'status', 'assignee', 'conversationTags', 'items' => fn ($q) => $q->orderBy('created_at')])
                ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds))
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
            ->select([
                'id', 'firstname', 'lastname', 'email', 'role', 'helpdesk_status',
                DB::raw('(SELECT COUNT(*) FROM helpdesk_conversations hc WHERE hc.assignee_id = users.id AND hc.closed_at IS NULL AND hc.deleted_at IS NULL) as open_count'),
            ])
            ->whereNull('deleted_at')
            ->orderBy('open_count')
            ->orderBy('firstname')
            ->get();

        return view('helpdesk::helpdesk.inbox.index', [
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
            'sidebarInboxes' => $inboxes,
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
    /**
     * Keep chatbot-handled conversations out of the inbox by default. The "bot"
     * view (?bot=1) lists them for supervision; a search bypasses the filter so
     * they remain findable in history.
     */
    private function applyBotVisibility(Builder $query, Request $request): void
    {
        if ($request->boolean('bot')) {
            $query->handledByBot();

            return;
        }

        if (! $request->filled('search')) {
            $query->withoutActiveBot();
        }
    }

    public function listJson(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $userInboxIds = $this->getUserInboxIds();

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
            ->with([
                'customer', 'status', 'assignee', 'inbox', 'lastMessage',
                'reads' => fn ($q) => $q->where('user_id', auth()->id()),
            ])
            ->withCount(['items as incoming_messages_count' => fn ($q) => $q->where('type', 'message')->whereNull('user_id')])
            ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds));

        // Conversations the chatbot is handling stay out of the inbox until it
        // hands off to an agent. The "bot" view shows them (supervision) and a
        // search still finds them (history).
        $this->applyBotVisibility($query, $request);

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

        $baseCount = Conversation::query()
            ->when($userInboxIds !== null, fn ($q) => $q->whereIn('inbox_id', $userInboxIds))
            ->withoutActiveBot();

        $html = view('helpdesk::helpdesk.inbox.partials.list', [
            'inboxGroups' => $inboxGroups,
            'totalConversations' => (clone $baseCount)->count(),
            'selectedConversationId' => $selectedId,
        ])->render();

        $counts = [
            'total' => $conversations->total(),
            'unread' => (int) (clone $baseCount)
                ->whereDoesntHave('reads', fn ($r) => $r->where('user_id', $userId))
                ->count(),
            'mine' => (int) (clone $baseCount)
                ->where('assignee_id', $userId)
                ->count(),
            'urgent' => (int) (clone $baseCount)
                ->where('priority', 'urgent')
                ->count(),
            'channels' => (clone $baseCount)
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

        return view('helpdesk::helpdesk.conversations.create', [
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
     * List enabled Mailer templates for the helpdesk module.
     */
    public function emailTemplates(): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $templates = MailerTemplate::query()
            ->module('helpdesk')
            ->enabled()
            ->with('translations')
            ->orderBy('name')
            ->get()
            ->map(fn (MailerTemplate $t) => [
                'id' => $t->id,
                'key' => $t->key,
                'name' => $t->name,
                'subject' => $t->subject ?? '',
            ]);

        return response()->json(['templates' => $templates]);
    }

    /**
     * Preview a Mailer template rendered with conversation variables.
     */
    public function previewEmailTemplate(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $templateId = (int) $request->input('template_id');
        $template = MailerTemplate::module('helpdesk')->enabled()->with(['translations', 'layout'])->find($templateId);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Plantilla no encontrada.'], 404);
        }

        $conversation->loadMissing(['customer', 'inbox']);
        $agent = auth()->user();

        $variables = [
            'CUSTOMER_NAME' => $conversation->customer?->name ?? 'Cliente',
            'CUSTOMER_EMAIL' => $conversation->customer?->email ?? '',
            'CONVERSATION_ID' => $conversation->id,
            'AGENT_NAME' => trim(($agent?->firstname ?? '').' '.($agent?->lastname ?? '')) ?: ($agent?->email ?? ''),
            'INBOX_NAME' => $conversation->inbox?->name ?? '',
            'COMPANY_NAME' => config('app.name'),
        ];

        $htmlBody = MailerTemplateRendererService::renderEmailTemplate($template, $variables);
        $plainBody = strip_tags(html_entity_decode(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)));
        $plainBody = trim(preg_replace('/[ \t]+/', ' ', preg_replace('/\n{3,}/', "\n\n", $plainBody)));

        $subject = MailerTemplateRendererService::replaceVariables($template->subject ?? '', $variables);

        return response()->json([
            'subject' => $subject,
            'body' => $plainBody,
            'html_body' => $htmlBody,
        ]);
    }

    public function previewJson(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load(['status', 'assignee']);

        $agentName = fn ($user) => trim(($user->firstname ?? '').' '.($user->lastname ?? '')) ?: 'Agente';

        $messages = $conversation->items()
            ->messages()
            ->with(['user:id,firstname,lastname', 'author:id,name'])
            ->orderBy('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'body' => mb_substr(strip_tags($item->html_body ?? $item->body ?? ''), 0, 600),
                'is_from_agent' => $item->isFromAgent(),
                'sender_name' => $item->isFromAgent() ? $agentName($item->user) : null,
                'created_at' => $item->created_at->translatedFormat('d M H:i'),
            ])
            ->values();

        $assigneeName = $conversation->assignee
            ? trim(($conversation->assignee->firstname ?? '').' '.($conversation->assignee->lastname ?? '')) ?: null
            : null;

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'subject' => $conversation->subject,
                'channel' => $conversation->channel,
                'status' => $conversation->status ? [
                    'name' => $conversation->status->name,
                    'is_open' => (bool) $conversation->status->is_open,
                ] : null,
                'assignee' => $assigneeName ? ['name' => $assigneeName] : null,
                'created_at' => $conversation->created_at->translatedFormat('d M Y'),
            ],
            'messages' => $messages,
            'open_url' => route('manager.helpdesk.conversations.index', ['selected' => $conversation->id]),
        ]);
    }

    /**
     * Send a real email to the customer and persist it as a ConversationItem.
     */
    public function sendEmail(SendEmailFromConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $validated = $request->validated();

        $conversation->loadMissing(['customer', 'inbox']);
        $customer = $conversation->customer;

        if (! filled($customer?->email)) {
            return response()->json([
                'success' => false,
                'message' => 'El contacto no tiene dirección de email.',
            ], 422);
        }

        $cc = $validated['cc'] ?? [];
        $bcc = $validated['bcc'] ?? [];

        // Si se eligió una plantilla del módulo Mailer, renderizar como HTML.
        if (! empty($validated['template_id'])) {
            $template = MailerTemplate::module('helpdesk')->enabled()
                ->with(['translations', 'layout'])
                ->find((int) $validated['template_id']);

            if ($template) {
                $agent = auth()->user();
                $variables = [
                    'CUSTOMER_NAME' => $customer->name ?? 'Cliente',
                    'CUSTOMER_EMAIL' => $customer->email ?? '',
                    'CONVERSATION_ID' => $conversation->id,
                    'TICKET_NUMBER' => (string) $conversation->id,
                    'SUBJECT' => $validated['subject'] ?? '',
                    'AGENT_NAME' => trim(($agent?->firstname ?? '').' '.($agent?->lastname ?? '')) ?: ($agent?->email ?? ''),
                    'INBOX_NAME' => $conversation->inbox?->name ?? '',
                    'COMPANY_NAME' => config('app.name'),
                ];
                $bodyHtml = MailerTemplateRendererService::renderEmailTemplate($template, $variables);
                $bodyPlain = strip_tags(html_entity_decode(preg_replace('/<br\s*\/?>/i', "\n", $bodyHtml)));
                $bodyPlain = trim(preg_replace('/[ \t]+/', ' ', preg_replace('/\n{3,}/', "\n\n", $bodyPlain)));
            } else {
                $bodyHtml = nl2br(e($validated['body']));
                $bodyPlain = $validated['body'];
            }
        } else {
            $bodyHtml = nl2br(e($validated['body']));
            $bodyPlain = $validated['body'];
        }

        $item = DB::transaction(function () use ($conversation, $validated, $cc, $bcc, $bodyHtml, $bodyPlain): ConversationItem {
            return $conversation->items()->create([
                'user_id' => auth()->id(),
                'type' => 'email_sent',
                'body' => $bodyPlain,
                'html_body' => $bodyHtml,
                'is_internal' => false,
                'metadata' => [
                    'subject' => $validated['subject'],
                    'cc' => $cc,
                    'bcc' => $bcc,
                    'template_key' => $validated['template_id'] ?? null,
                ],
            ]);
        });

        Mail::to($customer->email)
            ->cc($cc)
            ->bcc($bcc)
            ->queue(new CustomerOutboundEmail(
                conversation: $conversation,
                emailSubject: $validated['subject'],
                emailBodyHtml: $bodyHtml,
                emailBodyPlain: $bodyPlain,
                ccEmails: $cc,
                bccEmails: $bcc,
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
     * Legacy /conversations/{conversation} URL — redirects to the inbox with the
     * conversation pre-selected. The standalone "show" page was deprecated in
     * favor of the unified inbox layout (left list + thread + right panel).
     */
    public function show(Conversation $conversation, Request $request): RedirectResponse
    {
        $this->authorize('view', $conversation);

        return redirect()->route('manager.helpdesk.conversations.index', array_merge(
            ['selected' => $conversation->id],
            $request->only(['viewId', 'group', 'search'])
        ));
    }

    /**
     * Legacy /conversations/{conversation}/edit URL — redirects to the inbox
     * with the conversation pre-selected (editing is done in-place via modals).
     */
    public function edit(Conversation $conversation): RedirectResponse
    {
        $this->authorize('update', $conversation);

        return redirect()->route('manager.helpdesk.conversations.index', [
            'selected' => $conversation->id,
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

            $existingIds = $conversation->conversationTags()->pluck('tag_id')->map('intval')->all();
            $newIds = array_diff($tagIds, $existingIds);

            $conversation->conversationTags()->sync($tagIds);
            $conversation->load('conversationTags');

            foreach ($conversation->conversationTags->whereIn('id', $newIds) as $tag) {
                ConversationTagAdded::dispatch($conversation, $tag);
            }

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
            $oldGroupId = $conversation->group_id;
            $conversation->group_id = $request->input('group_id') ?: null;
            $conversation->save();

            // Remove old group tag if group changed
            if ($oldGroupId && $oldGroupId != $conversation->group_id) {
                $oldGroup = Group::find($oldGroupId);
                if ($oldGroup?->tag_id) {
                    $conversation->conversationTags()->detach($oldGroup->tag_id);
                }
            }

            // Attach new group tag
            if ($conversation->group_id) {
                $group = Group::find($conversation->group_id);
                if ($group?->tag_id) {
                    $conversation->conversationTags()->syncWithoutDetaching([$group->tag_id]);
                }
            }

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

        ConversationClosed::dispatch($conversation);

        // Fire CSAT survey unless explicitly skipped (best effort — never block close).
        if (! $request->boolean('skip_csat')) {
            try {
                app(CsatService::class)->dispatchForConversation($conversation);
            } catch (\Throwable $e) {
                \Log::warning('CSAT dispatch failed on close', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
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
                'channel' => $c->channel ?? 'web',
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
        $this->authorize('update', $conversation);

        $validated = $request->validated();

        [$item, $successMessage] = app(ConversationMessageService::class)->store($conversation, [
            'body' => $validated['body'] ?? '',
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
            // Read back to pick up metadata.link_preview enriched by the
            // ConversationItemLinkPreviewObserver (and add it inline as a
            // fallback so the agent panel always renders the unfurl card on
            // first paint without waiting for a follow-up broadcast).
            if ($item) {
                $reloaded = ConversationItem::with('user')->find($item->id);
                if ($reloaded) {
                    $item = $reloaded;
                }

                $existingMeta = $item->metadata ?? [];
                if (
                    ! ($validated['is_internal'] ?? false)
                    && ! isset($existingMeta['link_preview'])
                    && filled($item->body)
                ) {
                    $preview = app(LinkPreviewService::class)
                        ->previewFromBody((string) $item->body);
                    if ($preview !== null) {
                        $item->metadata = array_merge($existingMeta, ['link_preview' => $preview]);
                        $item->saveQuietly();
                    }
                }
            }

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
                    'attachment_urls' => $item?->attachment_urls ?? [],
                    'metadata' => $item?->metadata ?? [],
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
    /**
     * Toggle a reaction (emoji) on a conversation item. The agent can react
     * with one of a fixed set of emojis; reacting with the same emoji again
     * removes it.
     */
    public function reactToMessage(Request $request, ConversationItem $item): JsonResponse
    {
        $this->assertItemAccess($item);

        $emoji = (string) $request->input('emoji', '');
        if ($emoji === '') {
            return response()->json(['success' => false, 'message' => 'Emoji requerido'], 422);
        }

        $userId = (int) auth()->id();
        $metadata = $item->metadata ?? [];
        $reactions = collect($metadata['reactions'] ?? []);

        $existing = $reactions->first(
            fn ($r) => ($r['user_id'] ?? null) === $userId && ($r['emoji'] ?? null) === $emoji
        );

        if ($existing) {
            $reactions = $reactions->reject(
                fn ($r) => ($r['user_id'] ?? null) === $userId && ($r['emoji'] ?? null) === $emoji
            )->values();
            $action = 'removed';
        } else {
            $reactions = $reactions->reject(fn ($r) => ($r['user_id'] ?? null) === $userId)->values();
            $reactions->push([
                'user_id' => $userId,
                'emoji' => $emoji,
                'at' => now()->toIso8601String(),
            ]);
            $action = 'added';
        }

        $metadata['reactions'] = $reactions->values()->all();
        $item->metadata = $metadata;
        $item->save();

        return response()->json([
            'success' => true,
            'action' => $action,
            'emoji' => $emoji,
            'reactions' => $metadata['reactions'],
        ]);
    }

    /**
     * Forward an existing message to another customer (creates or reuses a
     * conversation with that customer and posts a copy of the body and
     * attachments).
     */
    public function forwardMessage(Request $request, ConversationItem $item): JsonResponse
    {
        $this->assertItemAccess($item);

        $customerId = (int) $request->input('customer_id');
        if ($customerId <= 0) {
            return response()->json(['success' => false, 'message' => 'Cliente requerido'], 422);
        }

        $targetCustomer = Customer::find($customerId);
        if (! $targetCustomer) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $sourceConv = $item->conversation;
        $defaultStatus = ConversationStatus::query()->where('is_default', true)->first()
            ?? ConversationStatus::query()->where('slug', 'open')->first();

        $targetConv = Conversation::firstOrCreate(
            [
                'customer_id' => $customerId,
                'status_id' => $defaultStatus?->id,
                'inbox_id' => $sourceConv?->inbox_id,
            ],
            [
                'channel' => $sourceConv?->channel ?? 'web',
                'priority' => 'normal',
                'last_message_at' => now(),
            ]
        );

        $forwarded = $targetConv->items()->create([
            'type' => 'message',
            'body' => $item->body,
            'html_body' => $item->html_body,
            'attachment_urls' => $item->attachment_urls,
            'metadata' => array_merge($item->metadata ?? [], [
                'forwarded_from' => $item->id,
                'forwarded_by' => auth()->id(),
                'forwarded_at' => now()->toIso8601String(),
            ]),
            'user_id' => auth()->id(),
            'is_internal' => false,
        ]);

        $targetConv->update(['last_message_at' => now()]);

        $this->deliverForwardedItem($targetConv, $forwarded);

        broadcast(new ConversationMessageCreated($forwarded, false))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Mensaje reenviado a '.($targetCustomer->name ?? $targetCustomer->email),
            'conversation_id' => $targetConv->id,
            'item_id' => $forwarded->id,
        ]);
    }

    /**
     * Deliver a forwarded item to the destination conversation's external channel
     * (whatsapp/facebook/instagram). Web/broadcast conversations are skipped.
     * Failures are logged and never break the forwarding flow.
     */
    private function deliverForwardedItem(Conversation $targetConv, ConversationItem $forwarded): void
    {
        $outbound = app(OutboundMessageService::class);
        if (! $outbound->supports($targetConv)) {
            return;
        }

        $externalId = null;

        $body = trim((string) $forwarded->body);
        if ($body !== '') {
            try {
                $externalId = $outbound->sendReply($targetConv, $body);
            } catch (\Throwable $e) {
                \Log::error('forwardMessage: outbound send failed', [
                    'conversation_id' => $targetConv->id,
                    'channel' => $targetConv->channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ((array) $forwarded->attachment_urls as $att) {
            $url = is_array($att) ? ($att['url'] ?? null) : $att;
            if (blank($url)) {
                continue;
            }

            try {
                $sent = $outbound->sendAttachment(
                    $targetConv,
                    is_array($att) ? (string) ($att['type'] ?? 'file') : 'file',
                    $this->absoluteUrl((string) $url),
                    null,
                    is_array($att) ? ($att['name'] ?? null) : null,
                );
                $externalId ??= $sent;
            } catch (\Throwable $e) {
                \Log::error('forwardMessage: outbound attachment send failed', [
                    'conversation_id' => $targetConv->id,
                    'channel' => $targetConv->channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($externalId) {
            $forwarded->external_id = $externalId;
            $forwarded->save();
        }
    }

    /**
     * Return delivery / read / metadata info for a single message.
     */
    public function messageInfo(ConversationItem $item): JsonResponse
    {
        $this->assertItemAccess($item);

        $item->loadMissing(['user', 'author', 'conversation']);
        $meta = $item->metadata ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'sent_at' => $item->created_at?->toIso8601String(),
                'delivered_at' => $meta['customer_delivered_at'] ?? null,
                'read_at' => $meta['customer_read_at'] ?? null,
                'author_name' => $item->user?->name ?? $item->author?->name ?? '—',
                'channel' => $item->conversation?->channel ?? '—',
                'external_id' => $item->external_id ?? '—',
                'is_internal' => (bool) $item->is_internal,
                'reactions' => $meta['reactions'] ?? [],
                'updated_at' => $item->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function downloadAttachment(Request $request): StreamedResponse
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

        // El adjunto debe pertenecer a una conversación a la que el agente
        // tenga acceso (evita IDOR: descargar adjuntos de inboxes ajenos).
        $item = ConversationItem::query()
            ->where('attachment_urls', 'like', '%'.$relPath.'%')
            ->with('conversation')
            ->first();

        if (! $item?->conversation) {
            abort(404, 'Archivo no encontrado');
        }

        $this->authorize('view', $item->conversation);

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
        $this->authorize('update', $conversation);

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
        $this->authorize('update', $conversation);

        $validated = $request->validated();
        $scheduledAt = Carbon::parse($validated['scheduled_at']);

        [$item] = app(ConversationMessageService::class)->store($conversation, array_merge($validated, [
            'scheduled_at' => $scheduledAt,
            'metadata' => array_merge($validated['metadata'] ?? [], [
                'scheduled' => true,
                'scheduled_by' => auth()->id(),
            ]),
        ]));

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
        $this->authorize('update', $conversation);

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

        // NOTE: MessageReceived (widget channel) is now broadcast by the
        // ConversationItemLinkPreviewObserver — single source of truth.

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
    public function forwardAttachment(ForwardAttachmentRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();

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

        return view('helpdesk::helpdesk.inbox.kanban', compact('statuses', 'byStatus'));
    }

    public function sendCsatSurvey(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $csatService = app(CsatService::class);
        $rating = $csatService->dispatchForConversation($conversation);

        if ($rating === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar la encuesta. El canal de esta conversación no es compatible.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Encuesta CSAT enviada al cliente.',
        ]);
    }

    /**
     * List internal notes (is_internal=true) for a conversation.
     */
    public function internalNotes(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $notes = $conversation->items()
            ->where('is_internal', true)
            ->with('user')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (ConversationItem $item) => [
                'id' => $item->id,
                'body' => $item->body,
                'author' => $item->user?->name ?? 'Agente',
                'initials' => $this->getInitials($item->user?->name ?? 'Agente'),
                'created_at' => $item->created_at?->toIso8601String(),
                'time_ago' => $item->created_at?->diffForHumans() ?? '—',
                'is_pinned' => (bool) ($item->metadata['is_pinned'] ?? false),
            ]);

        return response()->json(['success' => true, 'notes' => $notes]);
    }

    /**
     * List approved WhatsApp HSM templates.
     */
    public function hsmTemplates(): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $templates = WhatsAppTemplate::query()
            ->where('status', 'approved')
            ->orderBy('display_name')
            ->get()
            ->map(fn (WhatsAppTemplate $t) => [
                'id' => $t->id,
                'name' => $t->display_name,
                'body' => $t->body_template,
                'category' => $t->category,
                'header_type' => $t->header_type,
                'header_value' => $t->header_value,
                'footer_text' => $t->footer_text,
                'language' => $t->language,
                'param_count' => $t->param_count,
            ]);

        return response()->json(['success' => true, 'templates' => $templates]);
    }

    public function applyMacro(Conversation $conversation, Macro $macro): JsonResponse
    {
        $this->authorize('update', $conversation);

        $result = app(MacroExecutorService::class)->apply($macro, $conversation, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Macro aplicado',
            'executed' => $result['executed'] ?? [],
            'failed' => $result['failed'] ?? [],
        ]);
    }

    /**
     * Apply a single macro to many conversations at once. Each conversation is
     * guarded in its own try/catch so one failure does not abort the batch.
     */
    public function bulkApplyMacro(BulkApplyMacroRequest $request, MacroExecutorService $executor): JsonResponse
    {
        $validated = $request->validated();

        $macro = Macro::query()->active()->findOrFail($validated['macro_id']);

        $conversations = Conversation::query()
            ->with(['customer', 'inbox', 'assignee'])
            ->whereIn('id', $validated['conversation_ids'])
            ->get();

        $user = $request->user();
        $userId = $user->id;
        $canManage = $user->hasPermissionTo('helpdesk.manage');
        $canUpdateAll = $user->hasPermissionTo('helpdesk.conversations.update');
        $accessibleInboxIds = $canManage
            ? null
            : AgentInboxCapacity::query()->where('user_id', $userId)->pluck('inbox_id')->all();

        $applied = 0;
        $failed = 0;

        foreach ($conversations as $conversation) {
            if (! $this->canBulkUpdate($conversation, $userId, $canManage, $canUpdateAll, $accessibleInboxIds)) {
                $failed++;

                continue;
            }

            try {
                $executor->apply($macro, $conversation, $userId);
                $applied++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Bulk macro apply failed for conversation', [
                    'macro_id' => $macro->id,
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => $applied > 0,
            'applied' => $applied,
            'failed' => $failed,
            'message' => $failed === 0
                ? "Macro aplicado a {$applied} conversaciones."
                : "Macro aplicado a {$applied} conversaciones, {$failed} fallaron.",
        ]);
    }

    /**
     * In-memory mirror of ConversationPolicy::update for bulk batches: avoids a
     * per-conversation AgentInboxCapacity query by reusing preloaded inbox ids.
     *
     * @param  array<int, int>|null  $accessibleInboxIds  null when the user has helpdesk.manage
     */
    private function canBulkUpdate(
        Conversation $conversation,
        int $userId,
        bool $canManage,
        bool $canUpdateAll,
        ?array $accessibleInboxIds,
    ): bool {
        if (! $canUpdateAll && $conversation->assignee_id !== $userId) {
            return false;
        }

        if ($canManage) {
            return true;
        }

        return in_array($conversation->inbox_id, $accessibleInboxIds ?? [], true);
    }

    /**
     * List active macros for the inbox picker. When ?sort=used, macros are
     * ordered by usage (most used first) with a 'usados' flag on each entry.
     */
    public function macrosForPicker(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);

        $sortByUsage = $request->query('sort') === 'used';

        $query = Macro::query()
            ->active()
            ->where(function (Builder $q): void {
                $q->where('is_shared', true)
                    ->orWhere('user_id', auth()->id());
            });

        if ($sortByUsage) {
            $query->orderByDesc('usage_count')->orderByDesc('last_used_at');
        } else {
            $query->orderBy('name');
        }

        $macros = $query->get()->map(fn (Macro $macro): array => [
            'id' => $macro->id,
            'name' => $macro->name,
            'usageCount' => (int) $macro->usage_count,
            'usados' => (int) $macro->usage_count > 0,
            'lastUsedAt' => $macro->last_used_at?->toIso8601String(),
        ]);

        return response()->json(['success' => true, 'macros' => $macros]);
    }

    public function emailLogIndex(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->loadMissing('customer');
        $customerEmail = $conversation->customer?->email ?? '';

        // Build uid→EmailLog map for delivery status enrichment (optional module)
        $logMap = [];
        if (class_exists(EmailLog::class)) {
            EmailLog::query()
                ->select(EmailLog::LIST_COLUMNS)
                ->forEntity(Conversation::class, $conversation->id)
                ->get()
                ->each(function ($log) use (&$logMap) {
                    $logMap[$log->external_id ?? $log->uid] = $log;
                });
        }

        $items = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('type', 'email_sent')
            ->orderByDesc('created_at')
            ->get();

        $mapped = $items->map(function (ConversationItem $item) use ($customerEmail, $logMap) {
            $meta = $item->metadata ?? [];
            $log = $logMap[$item->external_id ?? ''] ?? null;

            $status = $log ? ($log->status?->value ?? 'queued') : 'queued';
            $statusLabel = match ($status) {
                'sent' => 'Enviado',
                'failed' => 'Fallido',
                default => 'En cola',
            };

            $attachCount = is_array($item->attachment_urls) ? count($item->attachment_urls) : 0;

            $preview = trim(preg_replace('/\s+/', ' ', strip_tags($item->body ?? '')));
            if (mb_strlen($preview) > 90) {
                $preview = mb_substr($preview, 0, 87).'…';
            }

            return [
                'uid' => (string) $item->id,
                'subject' => $meta['subject'] ?? '',
                'to' => $customerEmail,
                'status' => $status,
                'status_label' => $statusLabel,
                'preview' => $preview,
                'attachments_count' => $attachCount,
                'created_at' => $item->created_at?->toIso8601String(),
                'date_human' => $item->created_at?->diffForHumans() ?? '',
            ];
        });

        return response()->json([
            'emails' => $mapped,
            'counts' => [
                'all' => $mapped->count(),
                'sent' => $mapped->filter(fn ($e) => $e['status'] === 'sent')->count(),
                'failed' => $mapped->filter(fn ($e) => $e['status'] === 'failed')->count(),
            ],
        ]);
    }

    public function emailLogShow(Conversation $conversation, string $emailLog): JsonResponse
    {
        $this->authorize('view', $conversation);

        $item = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('type', 'email_sent')
            ->where('id', (int) $emailLog)
            ->with(['user:id,firstname,lastname,email'])
            ->firstOrFail();

        $meta = $item->metadata ?? [];
        $customerEmail = $conversation->customer?->email ?? '';

        // Enrich with delivery status from EmailLog if available
        $log = null;
        if (class_exists(EmailLog::class) && $item->external_id) {
            $log = EmailLog::query()
                ->forEntity(Conversation::class, $conversation->id)
                ->where('uid', $item->external_id)
                ->first();
        }

        $status = $log ? ($log->status?->value ?? 'queued') : 'queued';
        $statusLabel = match ($status) {
            'sent' => 'Enviado',
            'failed' => 'Fallido',
            default => 'En cola',
        };

        $sentAt = $log?->sent_at ?? $item->created_at;

        // Resolver nombre de plantilla
        $templateName = null;
        $templateKey = $meta['template_key'] ?? null;
        if ($templateKey && class_exists(MailerTemplate::class)) {
            $template = MailerTemplate::query()->find((int) $templateKey);
            $templateName = $template?->name;
        }

        // Enviado por: usuario autenticado o sistema
        $sentBy = $item->user
            ? trim(($item->user->firstname ?? '').' '.($item->user->lastname ?? '')) ?: $item->user->email
            : 'Sistema (automático)';

        // Tipo / categoría del email (si la plantilla la trae)
        $typeLabel = $meta['email_type_label'] ?? ($meta['email_type'] ?? null);

        // Documento relacionado (si fue agregado al metadata por automation)
        $relatedOrderId = $meta['related_order_id'] ?? null;
        $relatedCustomerName = $meta['related_customer_name'] ?? null;
        $relatedDocStatus = $meta['related_document_status'] ?? null;
        $relatedDocStatusCode = $meta['related_document_status_code'] ?? null;

        return response()->json([
            'uid' => (string) $item->id,
            'id_label' => '#EM-'.str_pad((string) $item->id, 4, '0', STR_PAD_LEFT),
            'subject' => $meta['subject'] ?? '',
            'to' => $customerEmail,
            'cc' => $meta['cc'] ?? [],
            'status' => $status,
            'status_label' => $statusLabel,
            'body_html' => $item->html_body ?: ($item->body ?? ''),
            'body_text' => trim(strip_tags($item->body ?? '')),
            'attachments' => $item->attachment_urls ?? [],
            'sent_at' => $sentAt?->toIso8601String(),
            'sent_at_human' => $sentAt?->diffForHumans(),
            'sent_at_formatted' => $sentAt?->format('d/m/Y H:i:s'),
            'created_at' => $item->created_at?->toIso8601String(),
            'error_message' => $log?->error_message,
            'template_name' => $templateName,
            'sent_by' => $sentBy,
            'type_label' => $typeLabel,
            'related_order_id' => $relatedOrderId,
            'related_customer_name' => $relatedCustomerName,
            'related_document_status' => $relatedDocStatus,
            'related_document_status_code' => $relatedDocStatusCode,
        ]);
    }

    public function conversationViewerItems(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->loadMissing(['customer', 'assignee', 'status', 'inbox']);

        $channelIcons = [
            'web' => 'far fa-comment-dots',
            'whatsapp' => 'fab fa-whatsapp',
            'facebook' => 'fab fa-facebook-f',
            'instagram' => 'fab fa-instagram',
            'email' => 'far fa-envelope',
            'twitter' => 'fab fa-twitter',
        ];

        $customer = $conversation->customer;
        $custName = $customer?->name ?? 'Cliente';
        $custInit = $this->getInitials($custName);
        $assignee = $conversation->assignee;
        $agentInit = $assignee ? $this->getInitials($assignee->name ?? '') : 'A';

        $items = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->whereNotIn('type', ['email_sent', 'event'])
            ->with(['user:id,firstname,lastname'])
            ->oldest('created_at')
            ->limit(200)
            ->get();

        $mapped = $items->map(function (ConversationItem $item) use ($custInit, $custName) {
            $isAgent = (bool) $item->user_id;
            $authorName = $isAgent
                ? trim(($item->user->firstname ?? '').' '.($item->user->lastname ?? '')) ?: 'Agente'
                : $custName;
            $initials = $isAgent ? $this->getInitials($authorName) : $custInit;
            $body = $item->type === 'activity'
                ? ($item->content ?? $item->body ?? '')
                : ($item->body ?? '');

            return [
                'id' => $item->id,
                'type' => $item->type,
                'body' => $body,
                'is_agent' => $isAgent,
                'is_internal' => $item->type === 'activity',
                'author_name' => $authorName,
                'author_initials' => $initials,
                'time_formatted' => $item->created_at?->format('H:i') ?? '',
                'date_label' => $this->dateLabelForItem($item->created_at),
                'attachment_urls' => $item->attachment_urls ?? [],
            ];
        });

        $grouped = [];
        $lastDate = null;

        foreach ($mapped as $m) {
            if ($m['date_label'] !== $lastDate) {
                $grouped[] = ['type' => 'day_separator', 'label' => $m['date_label']];
                $lastDate = $m['date_label'];
            }
            $grouped[] = $m;
        }

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'subject' => $conversation->subject ?? "Conversación #{$conversation->id}",
                'channel' => $conversation->channel ?? 'web',
                'channel_icon' => $channelIcons[$conversation->channel ?? 'web'] ?? 'far fa-comment-dots',
                'status_name' => $conversation->status?->name ?? 'Abierta',
                'is_open' => (bool) ($conversation->status?->is_open ?? true),
                'started_at_formatted' => $conversation->created_at?->format('d/m H:i') ?? '',
                'message_count' => $items->count(),
                'customer_name' => $custName,
                'customer_initials' => $custInit,
                'agent_name' => $assignee?->name ?? '',
                'agent_initials' => $agentInit,
            ],
            'items' => $grouped,
        ]);
    }

    public function auditLog(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->loadMissing(['customer', 'assignee']);

        $entries = [];

        // Conversation created
        $entries[] = [
            'ts' => $conversation->created_at?->format('d/m H:i') ?? '—',
            'ts_raw' => $conversation->created_at?->timestamp ?? 0,
            'action' => 'Conversación creada',
            'who' => 'Sistema · vía '.ucfirst($conversation->channel ?? 'widget'),
            'tag' => 'create',
            'category' => 'system',
        ];

        // Activity + message items
        $items = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->with(['user:id,firstname,lastname'])
            ->oldest('created_at')
            ->limit(500)
            ->get();

        foreach ($items as $item) {
            $agentName = $item->user
                ? trim(($item->user->firstname ?? '').' '.($item->user->lastname ?? '')) ?: 'Agente'
                : 'Sistema';

            if ($item->type === 'activity') {
                $body = trim(strip_tags($item->body ?? $item->content ?? ''));
                if (! $body) {
                    continue;
                }
                $bodyLower = mb_strtolower($body);
                $tag = match (true) {
                    str_contains($bodyLower, 'asigna') => 'assign',
                    str_contains($bodyLower, 'etiqueta') => 'tag',
                    str_contains($bodyLower, 'cerrada')
                        || str_contains($bodyLower, 'reabierta')
                        || str_contains($bodyLower, 'archivada')
                        || str_contains($bodyLower, 'estado') => 'state',
                    str_contains($bodyLower, 'prioridad') => 'update',
                    default => 'update',
                };
                $category = match ($tag) {
                    'assign' => 'assign',
                    'tag' => 'tag',
                    'state' => 'state',
                    default => 'update',
                };
                $entries[] = [
                    'ts' => $item->created_at?->format('d/m H:i') ?? '—',
                    'ts_raw' => $item->created_at?->timestamp ?? 0,
                    'action' => $body,
                    'who' => $agentName,
                    'tag' => $tag,
                    'category' => $category,
                ];

                continue;
            }

            if (in_array($item->type, ['message', 'incoming', 'outgoing', 'email_sent'], true)) {
                $entries[] = [
                    'ts' => $item->created_at?->format('d/m H:i') ?? '—',
                    'ts_raw' => $item->created_at?->timestamp ?? 0,
                    'action' => match ($item->type) {
                        'incoming' => 'Mensaje recibido del cliente',
                        'outgoing' => 'Mensaje enviado al cliente',
                        'email_sent' => 'Email enviado al cliente',
                        default => $item->user_id ? 'Respuesta del agente' : 'Mensaje del cliente',
                    },
                    'who' => $agentName,
                    'tag' => 'message',
                    'category' => 'message',
                ];
            }
        }

        usort($entries, fn ($a, $b) => $a['ts_raw'] <=> $b['ts_raw']);

        $counts = [
            'all' => count($entries),
            'assign' => count(array_filter($entries, fn ($e) => $e['category'] === 'assign')),
            'state' => count(array_filter($entries, fn ($e) => $e['category'] === 'state')),
            'tag' => count(array_filter($entries, fn ($e) => $e['category'] === 'tag')),
            'message' => count(array_filter($entries, fn ($e) => $e['category'] === 'message')),
            'update' => count(array_filter($entries, fn ($e) => $e['category'] === 'update')),
        ];

        foreach ($entries as &$e) {
            unset($e['ts_raw'], $e['category']);
        }

        return response()->json([
            'id' => $conversation->id,
            'subject' => $conversation->subject ?? "Conversación #{$conversation->id}",
            'counts' => $counts,
            'entries' => $entries,
        ]);
    }

    private function dateLabelForItem(?Carbon $dt): string
    {
        if (! $dt) {
            return 'Sin fecha';
        }

        if ($dt->isToday()) {
            return 'Hoy';
        }

        if ($dt->isYesterday()) {
            return 'Ayer';
        }

        return $dt->translatedFormat('D, d M');
    }

    private function getInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials = collect($parts)->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');

        return mb_strtoupper($initials);
    }
}
