<?php

namespace Modules\HelpdeskChatFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskChatFlow\Http\Requests\ImportChatFlowRequest;
use Modules\HelpdeskChatFlow\Http\Requests\StoreChatFlowRequest;
use Modules\HelpdeskChatFlow\Http\Requests\UpdateChatFlowRequest;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowExecution;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
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

    public function create(): View
    {
        $this->authorize('create', ChatFlow::class);

        $inboxes = Inbox::query()->orderBy('name')->get();

        return view('chatflow::editor', compact('inboxes'));
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

        $chatFlow->update(['status' => 'active', 'published_at' => now()]);

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
        $raw = $request->hasFile('file')
            ? file_get_contents($request->file('file')->getRealPath())
            : (string) $request->input('json');

        $data = json_decode($raw, true);

        if (! is_array($data) || empty($data['nodes']) || ! is_array($data['nodes'])) {
            return back()->with('error', 'El archivo no es un flow válido (falta «nodes»).');
        }

        $hasStart = collect($data['nodes'])->contains(fn ($n) => ($n['type'] ?? '') === 'start');
        if (! $hasStart) {
            return back()->with('error', 'El flow importado no tiene nodo de inicio.');
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

        $new = $chatFlow->replicate(['uid', 'published_at']);
        $new->uid = Str::uuid();
        $new->name = $chatFlow->name.' (copia)';
        $new->status = 'draft';
        $new->published_at = null;
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

    public function analytics(ChatFlow $chatFlow): View
    {
        $this->authorize('view', $chatFlow);

        $byStatus = $chatFlow->sessions()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalSessions = (int) $byStatus->sum();
        $completed = (int) ($byStatus['completed'] ?? 0);
        $transferred = (int) ($byStatus['transferred'] ?? 0);

        $summary = [
            'total' => $totalSessions,
            'completed' => $completed,
            'transferred' => $transferred,
            'abandoned' => (int) ($byStatus['abandoned'] ?? 0),
            'failed' => (int) ($byStatus['failed'] ?? 0),
            'active' => (int) ($byStatus['active'] ?? 0),
            'resolution_rate' => $totalSessions > 0
                ? round(($completed + $transferred) / $totalSessions * 100, 1)
                : 0.0,
        ];

        $dropOff = $this->buildDropOff($chatFlow);
        $aiMetrics = $this->buildAiMetrics($chatFlow);
        $csat = $this->buildCsatMetrics($chatFlow);

        return view('chatflow::analytics', compact('chatFlow', 'summary', 'dropOff', 'aiMetrics', 'csat'));
    }

    /**
     * Resolution metrics for the AI node: of the sessions that hit an `ai_response`
     * node, how many the bot resolved vs. escalated to an agent.
     *
     * @return array{used: int, resolved: int, escalated: int, rate: float}
     */
    private function buildAiMetrics(ChatFlow $chatFlow): array
    {
        $empty = ['used' => 0, 'resolved' => 0, 'escalated' => 0, 'rate' => 0.0];
        $sessionIds = $chatFlow->sessions()->pluck('id');

        if ($sessionIds->isEmpty()) {
            return $empty;
        }

        $aiSessionIds = ChatFlowExecution::query()
            ->whereIn('session_id', $sessionIds)
            ->where('node_type', 'ai_response')
            ->distinct()
            ->pluck('session_id');

        if ($aiSessionIds->isEmpty()) {
            return $empty;
        }

        $statusCounts = $chatFlow->sessions()
            ->whereIn('id', $aiSessionIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $resolved = (int) ($statusCounts['completed'] ?? 0);
        $escalated = (int) ($statusCounts['transferred'] ?? 0);
        $used = $aiSessionIds->count();

        return [
            'used' => $used,
            'resolved' => $resolved,
            'escalated' => $escalated,
            'rate' => $used > 0 ? round($resolved / $used * 100, 1) : 0.0,
        ];
    }

    /**
     * CSAT metrics: of the sessions that answered a satisfaction survey, the
     * average score and the share of satisfied customers. The scale (1-5, 1-10,
     * thumbs) is read from the flow's first csat node so the threshold matches.
     *
     * @return array{answered: int, average: float, satisfied: int, rate: float, max: int}
     */
    private function buildCsatMetrics(ChatFlow $chatFlow): array
    {
        $empty = ['answered' => 0, 'average' => 0.0, 'satisfied' => 0, 'rate' => 0.0, 'max' => 5];

        $csatNode = collect($chatFlow->nodes ?? [])->firstWhere('type', 'csat');
        $scale = $csatNode['data']['scale'] ?? '1-5';

        [$max, $threshold, $thumbs] = match ($scale) {
            'thumbs' => [2, 1, true],
            '1-10' => [10, 8, false],
            default => [5, 4, false],
        };

        $scores = $chatFlow->sessions()
            ->whereNotNull('context->csat_score')
            ->pluck('context')
            ->map(fn ($ctx) => is_array($ctx) ? ($ctx['csat_score'] ?? null) : null)
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0);

        if ($scores->isEmpty()) {
            return $empty;
        }

        $satisfied = $thumbs
            ? $scores->filter(fn ($v) => $v === 1)->count()
            : $scores->filter(fn ($v) => $v >= $threshold)->count();

        return [
            'answered' => $scores->count(),
            'average' => round($scores->avg(), 1),
            'satisfied' => $satisfied,
            'rate' => round($satisfied / $scores->count() * 100, 1),
            'max' => $max,
        ];
    }

    /**
     * Drop-off per node: how many sessions reached each node, and how many got
     * stuck/abandoned there (their last node) without completing the flow.
     *
     * @return array<int, array{node_id: string, label: string, type: string, reached: int, dropped: int, rate: float}>
     */
    private function buildDropOff(ChatFlow $chatFlow): array
    {
        $sessionIds = $chatFlow->sessions()->pluck('id');

        if ($sessionIds->isEmpty()) {
            return [];
        }

        // How many distinct sessions executed each node.
        $reached = ChatFlowExecution::query()
            ->whereIn('session_id', $sessionIds)
            ->selectRaw('node_id, count(distinct session_id) as total')
            ->groupBy('node_id')
            ->pluck('total', 'node_id');

        // Sessions that ended without completing — where did they stop?
        $dropped = $chatFlow->sessions()
            ->whereIn('status', ['abandoned', 'failed'])
            ->whereNotNull('current_node_id')
            ->selectRaw('current_node_id, count(*) as total')
            ->groupBy('current_node_id')
            ->pluck('total', 'current_node_id');

        $nodes = collect($chatFlow->nodes ?? [])
            ->filter(fn ($n) => ($n['type'] ?? '') !== 'branchItem');

        return $nodes->map(function ($node) use ($reached, $dropped) {
            $reachedCount = (int) ($reached[$node['id']] ?? 0);
            $droppedCount = (int) ($dropped[$node['id']] ?? 0);

            return [
                'node_id' => $node['id'],
                'label' => $node['label'] ?? $node['type'],
                'type' => $node['type'],
                'reached' => $reachedCount,
                'dropped' => $droppedCount,
                'rate' => $reachedCount > 0 ? round($droppedCount / $reachedCount * 100, 1) : 0.0,
            ];
        })
            ->filter(fn ($row) => $row['reached'] > 0 || $row['dropped'] > 0)
            ->sortByDesc('dropped')
            ->values()
            ->all();
    }
}
