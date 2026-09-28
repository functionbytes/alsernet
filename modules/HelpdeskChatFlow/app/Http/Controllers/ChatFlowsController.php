<?php

namespace Modules\HelpdeskChatFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskChatFlow\Http\Requests\StoreChatFlowRequest;
use Modules\HelpdeskChatFlow\Http\Requests\UpdateChatFlowRequest;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowTemplateLibrary;
use Modules\HelpdeskChatFlow\Services\ChatFlowTestRunner;
use Modules\HelpdeskChatFlow\Services\ChatFlowValidator;

class ChatFlowsController extends Controller
{
    public function __construct(
        private readonly ChatFlowTemplateLibrary $templates,
        private readonly ChatFlowValidator $validator,
        private readonly ChatFlowTestRunner $testRunner,
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

        // Bug real encontrado en QA (ago-2026): 'settings' no existe como rol
        // (solo 'super-settings', un permiso genérico no ligado a soporte) —
        // User::role() con un rol inexistente lanza RoleDoesNotExist, así que
        // esta pantalla no podía ni cargar. Mismo criterio de rol que ya usa
        // AssignmentService::getAvailableAgents()/CatalogCacheService::agents()
        // para "quién es agente real" en el resto de Helpdesk.
        $agents = User::role(['helpdesk-agent', 'helpdesk-admin', 'helpdesk-manager'])
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

        if ($request->boolean('skip_tests')) {
            Log::info('Chat flow publicado saltando escenarios de prueba', [
                'chat_flow_id' => $chatFlow->id,
                'user_id' => auth()->id(),
            ]);
        } else {
            $failingCases = $this->failingTestCaseNames($chatFlow);

            if (! empty($failingCases)) {
                $error = 'No se puede publicar: fallan los escenarios de prueba «'.implode('», «', $failingCases).'».';

                if ($request->expectsJson()) {
                    // failing_tests lets the editor offer an explicit "publish anyway" (skip_tests=1).
                    return response()->json(['success' => false, 'message' => $error, 'failing_tests' => $failingCases], 422);
                }

                return back()->with('error', $error);
            }
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

    /**
     * Re-runs every regression test case against the draft nodes about to be
     * published, updating each case's last result. Returns the names of the
     * ones that fail, so publish() can refuse to go live with a broken flow.
     *
     * @return array<int, string>
     */
    private function failingTestCaseNames(ChatFlow $chatFlow): array
    {
        $failing = [];

        foreach ($chatFlow->testCases as $testCase) {
            $result = $this->testRunner->run($chatFlow, $testCase->steps ?? []);

            $testCase->update([
                'last_result' => $result['passed'] ? 'passed' : 'failed',
                'last_run_at' => now(),
            ]);

            if (! $result['passed']) {
                $failing[] = $testCase->name;
            }
        }

        return $failing;
    }
}
