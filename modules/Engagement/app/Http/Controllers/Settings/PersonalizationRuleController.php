<?php

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Engagement\Concerns\RecordsAudit;
use Modules\Engagement\Models\PersonalizationRule;
use Modules\Helpdesk\Models\Inbox;

class PersonalizationRuleController extends Controller
{
    use RecordsAudit;

    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.personalizations.view')->only('page', 'index');
        $this->middleware('can:helpdesk.livechat.personalizations.create')->only('store');
        $this->middleware('can:helpdesk.livechat.personalizations.update')->only('update');
        $this->middleware('can:helpdesk.livechat.personalizations.delete')->only('destroy');
    }

    public function page(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        return view('engagement::settings.engagement.personalizations', compact('inboxes'));
    }

    public function index(Request $request): JsonResponse
    {
        $inboxId = $request->input('inbox_id');

        $rules = PersonalizationRule::query()
            ->when($inboxId, fn ($q) => $q->forInbox((int) $inboxId))
            ->active()
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
            'selector' => ['required', 'string', 'max:500'],
            'conditions' => ['nullable', 'array'],
            'conditions.operator' => ['required_with:conditions', 'in:AND,OR'],
            'conditions.rules' => ['required_with:conditions', 'array', 'min:1'],
            'mutation' => ['required', 'array'],
            'mutation.op' => ['required', 'in:text,attribute,insert_before,insert_after,class'],
            'is_active' => ['boolean'],
        ]);

        $rule = PersonalizationRule::query()->create($validated);
        Cache::forget("engagement:rules:personalization:{$rule->inbox_id}");
        $this->audit('created', 'personalization', $rule->id, $validated);

        return response()->json([
            'success' => true,
            'data' => $rule,
        ], 201);
    }

    public function update(Request $request, PersonalizationRule $personalizationRule): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'selector' => ['sometimes', 'string', 'max:500'],
            'conditions' => ['nullable', 'array'],
            'conditions.operator' => ['required_with:conditions', 'in:AND,OR'],
            'conditions.rules' => ['required_with:conditions', 'array', 'min:1'],
            'mutation' => ['sometimes', 'array'],
            'mutation.op' => ['required_with:mutation', 'in:text,attribute,insert_before,insert_after,class'],
            'is_active' => ['boolean'],
        ]);

        $personalizationRule->update($validated);
        Cache::forget("engagement:rules:personalization:{$personalizationRule->inbox_id}");
        $this->audit('updated', 'personalization', $personalizationRule->id, $validated);

        return response()->json([
            'success' => true,
            'data' => $personalizationRule->fresh(),
        ]);
    }

    public function destroy(PersonalizationRule $personalizationRule): JsonResponse
    {
        $id = $personalizationRule->id;
        $inboxId = $personalizationRule->inbox_id;
        $personalizationRule->delete();
        Cache::forget("engagement:rules:personalization:{$inboxId}");
        $this->audit('deleted', 'personalization', $id);

        return response()->json(['success' => true]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        $query = PersonalizationRule::query()->whereIn('id', $validated['ids']);
        $count = match ($validated['action']) {
            'activate' => $query->update(['is_active' => true]),
            'deactivate' => $query->update(['is_active' => false]),
            'delete' => $query->delete(),
        };

        $this->audit('bulk_'.$validated['action'], 'personalization', 0, ['ids' => $validated['ids']]);

        return response()->json(['success' => true, 'data' => ['affected' => $count]]);
    }
}
