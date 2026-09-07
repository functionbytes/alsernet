<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\Settings\BulkActionMacroRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreMacroRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateMacroRequest;
use Modules\HelpdeskTickets\Models\Macro;

class MacrosController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    public function index(Request $request): View
    {
        $query = Macro::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $macros = $query->orderBy('name')->paginate(20);

        $stats = [
            'total' => Macro::count(),
            'active' => Macro::where('is_active', true)->count(),
            'shared' => Macro::where('is_shared', true)->count(),
            'total_uses' => (int) Macro::sum('usage_count'),
        ];

        return view('helpdesktickets::managers.settings.macros.index', compact('macros', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesktickets::managers.settings.macros.create', [
            'actionTypes' => Macro::$actionTypes,
        ]);
    }

    public function store(StoreMacroRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['actions'] = json_decode($validated['actions'], true);
        $validated['is_shared'] = $request->boolean('is_shared', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['user_id'] = auth()->id();

        Macro::create($validated);

        return redirect()
            ->route('manager.helpdesk.settings.macros.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.macro.created'));
    }

    public function edit(Macro $macro): View
    {
        return view('helpdesktickets::managers.settings.macros.edit', [
            'macro' => $macro,
            'actionTypes' => Macro::$actionTypes,
        ]);
    }

    public function update(UpdateMacroRequest $request, Macro $macro): RedirectResponse
    {
        $validated = $request->validated();

        $validated['actions'] = json_decode($validated['actions'], true);
        $validated['is_shared'] = $request->boolean('is_shared', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        $macro->update($validated);

        return redirect()
            ->route('manager.helpdesk.settings.macros.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.macro.updated'));
    }

    public function destroy(Macro $macro): RedirectResponse
    {
        $macro->delete();

        return redirect()
            ->route('manager.helpdesk.settings.macros.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.macro.deleted'));
    }

    /**
     * Apply a bulk action (activate, deactivate or delete) to several macros.
     */
    public function bulkAction(BulkActionMacroRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids = $request->validated('ids');
        $count = 0;

        $macros = Macro::whereIn('id', $ids)->get();

        if ($action === 'delete') {
            foreach ($macros as $macro) {
                $macro->delete();
                $count++;
            }
        } else {
            $value = $action === 'activate';
            foreach ($macros as $macro) {
                $macro->update(['is_active' => $value]);
                $count++;
            }
        }

        $labels = ['delete' => 'eliminada(s)', 'activate' => 'activada(s)', 'deactivate' => 'desactivada(s)'];

        return response()->json([
            'message' => "{$count} macro(s) {$labels[$action]}.",
            'count' => $count,
        ]);
    }
}
