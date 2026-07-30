<?php

namespace Modules\HelpdeskChatFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskChatFlow\Http\Requests\ImportChatFlowRequest;
use Modules\HelpdeskChatFlow\Http\Requests\StoreChatFlowRequest;
use Modules\HelpdeskChatFlow\Http\Requests\UpdateChatFlowRequest;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowAnalyticsService;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;
use Modules\HelpdeskChatFlow\Services\ChatFlowReplayService;
use Modules\HelpdeskChatFlow\Services\ChatFlowTemplateLibrary;
use Modules\HelpdeskChatFlow\Services\ChatFlowValidator;

class ChatFlowsController extends Controller
{
    public function __construct(
        private readonly ChatFlowTemplateLibrary $templates,
        private readonly ChatFlowValidator $validator,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', ChatFlow::class);

        $chatFlows = ChatFlow::query()
            ->with('inbox')
            ->withCount('sessions')
            ->when(request('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when(request('trigger_type'), fn ($q, $t) => $q->where('trigger_type', $t))
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $flowCounts = ChatFlow::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'active' => (int) ($flowCounts['active'] ?? 0),
            'draft' => (int) ($flowCounts['draft'] ?? 0),
            // Range instead of whereDate(): keeps the started_at index usable.
            'sessions_today' => ChatFlowSession::query()
                ->where('started_at', '>=', today())
                ->count(),
        ];

        $templates = $this->templates->all();

        return view('chatflow::index', compact('chatFlows', 'stats', 'templates'));
    }

    public function storeFromTemplate(string $template): RedirectResponse
    {
        $this->authorize('create', ChatFlow::class);

        $built = $this->templates->build($template);

        if (! $built) {
            return redirect()->route('chatflow.index')->with('error', 'Plantilla no encontrada.');
        }

        $flow = ChatFlow::create([
            'uid' => Str::uuid(),
            'name' => $built['name'],
            'description' => $built['description'],
            'trigger_type' => $built['trigger_type'],
            'nodes' => $built['nodes'],
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('chatflow.edit', $flow)
            ->with('success', "Flow «{$built['name']}» creado desde plantilla. Revísalo y publícalo.");
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', ChatFlow::class);

        // El editor React es solo-edición (hace axios.put a saveUrl), así que no
        // puede renderizarse sin un flow persistido. Crear "Nuevo flow" genera un
        // borrador en blanco con nodo inicio/fin y abre su editor, alineado con el
        // store()→edit ya existente.
        $flow = ChatFlow::create([
            'uid' => Str::uuid(),
            'name' => 'Nuevo flow',
            'nodes' => [
                ['id' => 'n1', 'type' => 'start', 'config' => []],
                ['id' => 'n2', 'type' => 'end', 'config' => []],
            ],
            'edges' => [
                ['source' => 'n1', 'target' => 'n2'],
            ],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('chatflow.edit', $flow);
    }

    public function store(StoreChatFlowRequest $request): RedirectResponse
    {
        $flow = ChatFlow::create([
            ...$request->validated(),
            'uid' => Str::uuid(),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('chatflow.edit', $flow)->with('success', 'Flow creado correctamente.');
    }

    public function edit(ChatFlow $chatFlow): View
    {
        $this->authorize('update', $chatFlow);

        $inboxes = Inbox::query()->orderBy('name')->get();

        $agents = User::role(['administrative', 'manager', 'settings', 'super-settings'])
            ->orderBy('firstname')
            ->get(['id', 'firstname', 'lastname'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => trim($u->firstname.' '.$u->lastname) ?: $u->email]);

        $groups = Group::query()->orderBy('name')->get(['id', 'name']);

        return view('chatflow::editor', compact('chatFlow', 'inboxes', 'agents', 'groups'));
    }

    public function update(UpdateChatFlowRequest $request, ChatFlow $chatFlow): RedirectResponse|JsonResponse
    {
        $chatFlow->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Flow actualizado correctamente.']);
        }

        return back()->with('success', 'Flow actualizado correctamente.');
    }

    public function destroy(ChatFlow $chatFlow): RedirectResponse
    {
        $this->authorize('delete', $chatFlow);

        $chatFlow->delete();

        return redirect()->route('chatflow.index')->with('success', 'Flow eliminado.');
    }

    /**
     * Supervisor takes over a conversation the bot is handling: stops the bot
     * session, releases it to the inbox and assigns it to the current agent.
     */
    public function takeOver(int $conversationId, ChatFlowEngine $engine): JsonResponse
    {
        $this->authorize('takeOver', ChatFlow::class);

        $conversation = Conversation::on('helpdesk')->findOrFail($conversationId);

        // Only act on conversations actually handled by the bot — keeps the
        // endpoint scoped to its purpose (the route is already gated to supervisors).
        abort_unless($conversation->metadata['handled_by_bot'] ?? false, 422, 'La conversación no está siendo atendida por el bot.');

        $engine->takeOver($conversation, (int) auth()->id());

        return response()->json(['success' => true, 'message' => 'Has tomado el control de la conversación.']);
    }

    public function publish(Request $request, ChatFlow $chatFlow): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $chatFlow);

        $result = $this->validator->validate($chatFlow);

        if (! empty($result['errors'])) {
            $error = 'No se puede publicar: '.implode(' ', $result['errors']);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }

            return back()->with('error', $error);
        }

        // Snapshot the current node tree before activating, so it can be restored.
        $chatFlow->versions()->create([
            'name' => $chatFlow->name,
            'nodes' => $chatFlow->nodes,
            'created_by' => auth()->id(),
        ]);

        // Promote the working draft to the published snapshot: the runtime engine
        // executes `published_nodes`, so the bot only switches to the new graph at
        // publish time — never while the designer is still editing the draft.
        $chatFlow->update([
            'status' => 'active',
            'published_nodes' => $chatFlow->nodes,
            'published_at' => now(),
        ]);

        $message = 'Flow publicado y activo.';
        if (! empty($result['warnings'])) {
            $message .= ' Avisos: '.implode(' ', $result['warnings']);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function versions(ChatFlow $chatFlow): View
    {
        $this->authorize('view', $chatFlow);

        $versions = $chatFlow->versions()->paginate(20);

        return view('chatflow::versions', compact('chatFlow', 'versions'));
    }

    public function restoreVersion(ChatFlow $chatFlow, int $version): RedirectResponse
    {
        $this->authorize('update', $chatFlow);

        $snapshot = $chatFlow->versions()->whereKey($version)->firstOrFail();

        $chatFlow->update(['nodes' => $snapshot->nodes]);

        return redirect()->route('chatflow.edit', $chatFlow)
            ->with('success', 'Versión restaurada. Revísala y vuelve a publicar.');
    }

    public function export(ChatFlow $chatFlow): JsonResponse
    {
        $this->authorize('view', $chatFlow);

        $payload = [
            'format' => 'chatflow/v1',
            'name' => $chatFlow->name,
            'description' => $chatFlow->description,
            'trigger_type' => $chatFlow->trigger_type,
            'trigger_conditions' => $chatFlow->trigger_conditions,
            'nodes' => $chatFlow->nodes,
            'exported_at' => now()->toIso8601String(),
        ];

        $filename = Str::slug($chatFlow->name ?: 'chatflow').'-flow.json';

        return response()->json($payload, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function import(ImportChatFlowRequest $request): RedirectResponse
    {
        // Structural validation (JSON shape, node/edge caps, node types, start
        // node, trigger_type and trigger_conditions shape) lives in
        // ImportChatFlowRequest. Here only the runtime graph validation runs —
        // the same validator used on publish, so we never import a flow that
        // would break at runtime (broken go_to_step, unreachable nodes, etc.).
        $data = $request->flowData();

        $candidate = new ChatFlow(['nodes' => $data['nodes']]);
        $validation = $this->validator->validate($candidate);

        if (! empty($validation['errors'])) {
            return back()->with('error', 'El flow importado no es válido: '.implode(' ', $validation['errors']));
        }

        $flow = ChatFlow::create([
            'uid' => Str::uuid(),
            'name' => ($data['name'] ?? 'Flow importado').' (importado)',
            'description' => $data['description'] ?? null,
            'trigger_type' => in_array($data['trigger_type'] ?? '', ChatFlow::TRIGGER_TYPES, true)
                ? $data['trigger_type']
                : 'conversation_start',
            'trigger_conditions' => is_array($data['trigger_conditions'] ?? null) ? $data['trigger_conditions'] : null,
            'nodes' => $data['nodes'],
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('chatflow.edit', $flow)->with('success', 'Flow importado como borrador. Revísalo y publícalo.');
    }

    public function duplicate(ChatFlow $chatFlow): RedirectResponse
    {
        $this->authorize('create', ChatFlow::class);

        $new = $chatFlow->replicate(['uid', 'published_at', 'published_nodes']);
        $new->uid = Str::uuid();
        $new->name = $chatFlow->name.' (copia)';
        $new->status = 'draft';
        $new->published_at = null;
        $new->published_nodes = null;
        $new->created_by = auth()->id();
        $new->save();

        return redirect()->route('chatflow.edit', $new)->with('success', 'Flow duplicado correctamente.');
    }

    public function sessions(ChatFlow $chatFlow): View
    {
        $this->authorize('view', $chatFlow);

        $sessions = $chatFlow->sessions()
            ->with('conversation.customer')
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('started_at')
            ->paginate(30)
            ->withQueryString();

        return view('chatflow::sessions', compact('chatFlow', 'sessions'));
    }

    public function replaySession(ChatFlow $chatFlow, ChatFlowSession $session, ChatFlowReplayService $replay): View
    {
        $this->authorize('view', $chatFlow);
        abort_unless($session->chat_flow_id === $chatFlow->id, 404);

        $result = $replay->replay($session);

        return view('chatflow::replay', compact('chatFlow', 'session', 'result'));
    }

    /** Selectable analytics windows (days); 0 = all-time. Shared with cache invalidation. */
    public const ANALYTICS_DAY_KEYS = [7, 30, 90, 365, 0];

    public static function analyticsCacheKey(int $flowId, int $days): string
    {
        return "helpdeskchatflow:analytics:{$flowId}:{$days}";
    }

    public function analytics(Request $request, ChatFlow $chatFlow, ChatFlowAnalyticsService $analytics): View
    {
        $this->authorize('view', $chatFlow);

        $range = $this->resolveAnalyticsRange($request);

        // The metric blocks scan the flow's whole session history; cache them per
        // (flow, days). Invalidated by InvalidateFlowAnalyticsCache when a session
        // of this flow completes, so the numbers stay fresh without re-querying on
        // every dashboard open. TTL is a safety net for other mutations.
        $metrics = Cache::remember(
            self::analyticsCacheKey($chatFlow->id, $range['days']),
            now()->addMinutes(15),
            function () use ($chatFlow, $range, $analytics): array {
                $from = $range['from'];
                $summary = $analytics->buildSummary($chatFlow, $from);
                $csat = $analytics->buildCsatMetrics($chatFlow, $from);
                $aiMetrics = $analytics->buildAiMetrics($chatFlow, $from);

                return [
                    'summary' => $summary,
                    'dropOff' => $analytics->buildDropOff($chatFlow, $from),
                    'aiMetrics' => $aiMetrics,
                    'csat' => $csat,
                    'comparison' => $analytics->buildAbComparison($chatFlow, $from, [
                        'summary' => $summary,
                        'csat' => $csat,
                        'ai' => $aiMetrics,
                    ]),
                ];
            }
        );

        return view('chatflow::analytics', array_merge(
            ['chatFlow' => $chatFlow, 'range' => $range],
            $metrics
        ));
    }

    /**
     * Resolve the analytics time window from the request. Defaults to the last
     * 30 days so high-volume flows don't full-scan their session history; an
     * "all time" option (`days=0`) recovers the previous unbounded behaviour.
     *
     * @return array{days: int, from: ?Carbon, options: array<int, string>}
     */
    private function resolveAnalyticsRange(Request $request): array
    {
        $options = [
            7 => 'Últimos 7 días',
            30 => 'Últimos 30 días',
            90 => 'Últimos 90 días',
            365 => 'Último año',
            0 => 'Todo el histórico',
        ];

        $days = (int) $request->input('days', 30);
        if (! array_key_exists($days, $options)) {
            $days = 30;
        }

        return [
            'days' => $days,
            'from' => $days > 0 ? now()->subDays($days)->startOfDay() : null,
            'options' => $options,
        ];
    }
}
