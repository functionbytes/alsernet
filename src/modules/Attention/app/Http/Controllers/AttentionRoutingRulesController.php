<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Attention\Http\Requests\StoreRoutingRuleRequest;
use Modules\Attention\Models\AttentionCategory;
use Modules\Attention\Models\AttentionDepartment;
use Modules\Attention\Models\AttentionRoutingRule;
use Modules\Attention\Models\AttentionSede;
use Modules\Attention\Models\AttentionType;

class AttentionRoutingRulesController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('attention.settings');

        $stats = AttentionRoutingRule::query()->selectRaw('
            COUNT(*) as total,
            SUM(is_active = 1) as active,
            SUM(is_active = 0) as inactive,
            SUM(condition_type_id IS NULL AND condition_category_id IS NULL AND condition_sede_id IS NULL) as catch_all
        ')->first();

        $query = AttentionRoutingRule::with([
            'conditionType',
            'conditionCategory',
            'conditionSede',
            'assignedUser',
            'assignedDepartment',
        ])->orderBy('priority');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $rules = $query->paginate(20)->withQueryString();

        return view('attention::settings.routing-rules.index', compact('rules', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('attention.settings');

        return view('attention::settings.routing-rules.create', $this->formData());
    }

    public function store(StoreRoutingRuleRequest $request): RedirectResponse
    {
        $this->authorize('attention.settings');

        AttentionRoutingRule::create($request->validated());

        return redirect()
            ->route('settings.attention.routing-rules.index')
            ->with('success', 'Regla de asignación creada correctamente.');
    }

    public function edit(AttentionRoutingRule $routingRule): View
    {
        $this->authorize('attention.settings');

        return view('attention::settings.routing-rules.edit', [
            'rule' => $routingRule,
            ...$this->formData(),
        ]);
    }

    public function update(StoreRoutingRuleRequest $request, AttentionRoutingRule $routingRule): RedirectResponse
    {
        $this->authorize('attention.settings');

        $routingRule->update($request->validated());

        return redirect()
            ->route('settings.attention.routing-rules.index')
            ->with('success', 'Regla de asignación actualizada correctamente.');
    }

    public function destroy(AttentionRoutingRule $routingRule): RedirectResponse
    {
        $this->authorize('attention.settings');

        $routingRule->delete();

        return back()->with('success', 'Regla eliminada correctamente.');
    }

    public function toggle(AttentionRoutingRule $routingRule): JsonResponse
    {
        $this->authorize('attention.settings');

        $routingRule->update(['is_active' => ! $routingRule->is_active]);

        return response()->json(['is_active' => $routingRule->is_active]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('attention.settings');

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $rules = AttentionRoutingRule::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($rules as $rule) {
            match ($validated['action']) {
                'activate' => $rule->update(['is_active' => true]),
                'deactivate' => $rule->update(['is_active' => false]),
                'delete' => $rule->delete(),
            };
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'users' => User::orderBy('firstname')->get(['id', 'firstname', 'lastname']),
            'types' => AttentionType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => AttentionCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'sedes' => AttentionSede::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => AttentionDepartment::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
