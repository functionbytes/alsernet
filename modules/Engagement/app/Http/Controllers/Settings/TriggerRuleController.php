<?php

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Engagement\Concerns\RecordsAudit;
use Modules\Engagement\Models\TriggerRule;
use Modules\Helpdesk\Models\Inbox;

class TriggerRuleController extends Controller
{
    use RecordsAudit;

    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.triggers.view')->only('page', 'index');
        $this->middleware('can:helpdesk.livechat.triggers.create')->only('store');
        $this->middleware('can:helpdesk.livechat.triggers.update')->only('update');
        $this->middleware('can:helpdesk.livechat.triggers.delete')->only('destroy');
    }

    public function page(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        return view('engagement::settings.engagement.triggers', compact('inboxes'));
    }

    public function index(Request $request): JsonResponse
    {
        $inboxId = $request->input('inbox_id');

        $rules = TriggerRule::query()
            ->when($inboxId, fn ($q) => $q->forInbox((int) $inboxId))
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inbox_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'conditions' => ['required', 'array'],
            'conditions.operator' => ['required', 'in:AND,OR'],
            'conditions.rules' => ['required', 'array', 'min:1'],
            'action' => ['required', 'array'],
            'action.type' => ['required', 'in:open_chat,show_banner,redirect,callback'],
            'priority' => ['integer', 'min:0', 'max:100'],
            'fires_per_session' => ['integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $rule = TriggerRule::query()->create($validated);
        Cache::forget("engagement:rules:trigger:{$rule->inbox_id}");
        $this->audit('created', 'trigger', $rule->id, $validated);

        return response()->json([
            'success' => true,
            'data' => $rule,
        ], 201);
    }

    public function update(Request $request, TriggerRule $triggerRule): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'conditions' => ['sometimes', 'array'],
            'conditions.operator' => ['required_with:conditions', 'in:AND,OR'],
            'conditions.rules' => ['required_with:conditions', 'array', 'min:1'],
            'action' => ['sometimes', 'array'],
            'action.type' => ['required_with:action', 'in:open_chat,show_banner,redirect,callback'],
            'priority' => ['integer', 'min:0', 'max:100'],
            'fires_per_session' => ['integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $triggerRule->update($validated);
        Cache::forget("engagement:rules:trigger:{$triggerRule->inbox_id}");
        $this->audit('updated', 'trigger', $triggerRule->id, $validated);

        return response()->json([
            'success' => true,
            'data' => $triggerRule->fresh(),
        ]);
    }

    public function destroy(TriggerRule $triggerRule): JsonResponse
    {
        $id = $triggerRule->id;
        $inboxId = $triggerRule->inbox_id;
        $triggerRule->delete();
        Cache::forget("engagement:rules:trigger:{$inboxId}");
        $this->audit('deleted', 'trigger', $id);

        return response()->json(['success' => true]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        $query = TriggerRule::query()->whereIn('id', $validated['ids']);
        $count = match ($validated['action']) {
            'activate' => $query->update(['is_active' => true]),
            'deactivate' => $query->update(['is_active' => false]),
            'delete' => $query->delete(),
        };

        $this->audit('bulk_'.$validated['action'], 'trigger', 0, ['ids' => $validated['ids']]);

        return response()->json(['success' => true, 'data' => ['affected' => $count]]);
    }
}
