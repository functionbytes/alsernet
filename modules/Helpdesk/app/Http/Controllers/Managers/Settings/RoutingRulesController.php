<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Managers\Settings\StoreRoutingRuleRequest;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateRoutingRuleRequest;
use Modules\Helpdesk\Models\RoutingRule;

class RoutingRulesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only(['index', 'create', 'edit']);
        $this->middleware('can:helpdesk.settings.update')->only(['store', 'update', 'toggle']);
        $this->middleware('can:helpdesk.settings.update')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $query = RoutingRule::query();

        if ($request->filled('search')) {
            $query->where('keyword', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rules = $query->orderBy('priority')->orderBy('id')->paginate(20);

        $statsRow = RoutingRule::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        ')->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'active' => (int) $statsRow->active,
            'inactive' => (int) $statsRow->inactive,
        ];

        return view('helpdesk::settings.routing-rules.index', compact('rules', 'stats'));
    }

    public function create(): View
    {
        $agents = User::query()->orderBy('firstname')->get(['id', 'firstname', 'lastname']);

        return view('helpdesk::settings.routing-rules.create', compact('agents'));
    }

    public function store(StoreRoutingRuleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        RoutingRule::create($validated);

        return redirect()
            ->route('settings.helpdesk.routing-rules.index')
            ->with('success', 'Regla de enrutamiento creada exitosamente.');
    }

    public function edit(RoutingRule $routingRule): View
    {
        $agents = User::query()->orderBy('firstname')->get(['id', 'firstname', 'lastname']);

        return view('helpdesk::settings.routing-rules.edit', compact('routingRule', 'agents'));
    }

    public function update(UpdateRoutingRuleRequest $request, RoutingRule $routingRule): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $routingRule->update($validated);

        return redirect()
            ->route('settings.helpdesk.routing-rules.index')
            ->with('success', 'Regla de enrutamiento actualizada exitosamente.');
    }

    public function destroy(RoutingRule $routingRule): RedirectResponse
    {
        $routingRule->delete();

        return redirect()
            ->route('settings.helpdesk.routing-rules.index')
            ->with('success', 'Regla de enrutamiento eliminada exitosamente.');
    }

    public function toggle(RoutingRule $routingRule): JsonResponse
    {
        $routingRule->update(['is_active' => ! $routingRule->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $routingRule->is_active,
            'message' => $routingRule->is_active ? 'Regla activada.' : 'Regla desactivada.',
        ]);
    }
}
