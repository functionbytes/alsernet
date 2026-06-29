<?php

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Engagement\Concerns\RecordsAudit;
use Modules\Engagement\Models\AutomationFlow;
use Modules\Helpdesk\Models\Inbox;

class AutomationFlowController extends Controller
{
    use RecordsAudit;

    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.automation.view')->only('page', 'index', 'show');
        $this->middleware('can:helpdesk.livechat.automation.create')->only('store');
        $this->middleware('can:helpdesk.livechat.automation.update')->only('update');
        $this->middleware('can:helpdesk.livechat.automation.delete')->only('destroy');
    }

    public function page(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        return view('engagement::settings.engagement.automation', compact('inboxes'));
    }

    public function index(Request $request): JsonResponse
    {
        $rows = AutomationFlow::query()
            ->when($request->input('inbox_id'), fn ($q, $id) => $q->forInbox((int) $id))
            ->withCount('runs')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(AutomationFlow $automationFlow): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $automationFlow]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateFlow($request);
        $flow = AutomationFlow::query()->create($validated);
        $this->audit('created', 'automation_flow', $flow->id, ['name' => $flow->name]);

        return response()->json(['success' => true, 'data' => $flow], 201);
    }

    public function update(Request $request, AutomationFlow $automationFlow): JsonResponse
    {
        $validated = $this->validateFlow($request, true);
        $automationFlow->update($validated);
        $this->audit('updated', 'automation_flow', $automationFlow->id);

        return response()->json(['success' => true, 'data' => $automationFlow->fresh()]);
    }

    public function destroy(AutomationFlow $automationFlow): JsonResponse
    {
        $id = $automationFlow->id;
        $automationFlow->delete();
        $this->audit('deleted', 'automation_flow', $id);

        return response()->json(['success' => true]);
    }

    private function validateFlow(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'inbox_id' => [$required, 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'name' => [$required, 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'trigger_type' => [$required, 'in:manual,event,schedule'],
            'trigger_event' => ['nullable', 'string', 'max:100'],
            'nodes' => [$required, 'array', 'min:1'],
            'nodes.*.id' => ['required', 'string'],
            'nodes.*.type' => ['required', 'in:message,question,delay,condition,action'],
            'nodes.*.config' => ['nullable', 'array'],
            'edges' => [$required, 'array'],
            'edges.*.from' => ['required', 'string'],
            'edges.*.to' => ['required', 'string'],
            'edges.*.branch' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }
}
