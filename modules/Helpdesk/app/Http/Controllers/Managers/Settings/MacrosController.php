<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\StoreMacroRequest;
use Modules\Helpdesk\Http\Requests\UpdateMacroRequest;
use Modules\Helpdesk\Models\Macro;

class MacrosController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.macros.view')->only(['index']);
        $this->middleware('can:helpdesk.macros.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.macros.update')->only(['edit', 'update']);
        $this->middleware('can:helpdesk.macros.delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $query = Macro::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('visibility')) {
            $query->where('is_shared', $request->visibility === 'shared' ? 1 : 0);
        }

        $macros = $query->latest()->paginate(20);

        $statsRow = Macro::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_shared = 1 THEN 1 ELSE 0 END) as shared_count,
            SUM(CASE WHEN is_shared = 0 THEN 1 ELSE 0 END) as personal_count,
            SUM(usage_count) as total_runs
        ')->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'shared' => (int) $statsRow->shared_count,
            'personal' => (int) $statsRow->personal_count,
            'total_runs' => (int) $statsRow->total_runs,
        ];

        return view('helpdesk::settings.macros.index', compact('macros', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.macros.create', [
            'actionTypes' => Macro::ACTION_TYPES,
        ]);
    }

    public function store(StoreMacroRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_shared'] = $request->boolean('is_shared', true);
        $data['user_id'] = ! $data['is_shared'] ? $request->user()->id : null;
        unset($data['visibility']);

        Macro::create($data);

        return redirect()
            ->route('settings.helpdesk.macros.index')
            ->with('success', 'Macro creado exitosamente.');
    }

    public function edit(Macro $macro): View
    {
        return view('helpdesk::settings.macros.edit', [
            'macro' => $macro,
            'actionTypes' => Macro::ACTION_TYPES,
        ]);
    }

    public function update(UpdateMacroRequest $request, Macro $macro): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['is_shared'] = $request->boolean('is_shared');
        $data['user_id'] = ! $data['is_shared'] ? ($macro->user_id ?? $request->user()->id) : null;
        unset($data['visibility']);

        $macro->update($data);

        return redirect()
            ->route('settings.helpdesk.macros.index')
            ->with('success', 'Macro actualizado exitosamente.');
    }

    public function destroy(Macro $macro): RedirectResponse
    {
        $macro->delete();

        return redirect()
            ->route('settings.helpdesk.macros.index')
            ->with('success', 'Macro eliminado exitosamente.');
    }
}
