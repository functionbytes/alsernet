<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Attention\Http\Requests\CreateAttentionSedeRequest;
use Modules\Attention\Http\Requests\UpdateAttentionSedeRequest;
use Modules\Attention\Models\AttentionSede;

class AttentionSedesController extends Controller
{
    /**
     * Display a listing of sedes.
     */
    public function index(Request $request): View
    {
        $query = AttentionSede::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // City filter
        if ($request->filled('city')) {
            $query->where('city', 'like', "%{$request->city}%");
        }

        $sedes = $query->withCount('attentions')->orderBy('name')->paginate(paginationNumber());

        $counts = AttentionSede::query()
            ->selectRaw('COUNT(*) as total, SUM(is_active = 1) as active, SUM(is_active = 0) as inactive, COUNT(DISTINCT city) as cities')
            ->first();

        $stats = [
            'total' => (int) $counts->total,
            'active' => (int) $counts->active,
            'inactive' => (int) $counts->inactive,
            'cities' => (int) $counts->cities,
        ];

        // Get list of cities for filter
        $cities = AttentionSede::distinct()
            ->orderBy('city')
            ->pluck('city')
            ->filter();

        return view('attention::settings.sedes.index', [
            'sedes' => $sedes,
            'stats' => $stats,
            'cities' => $cities,
        ]);
    }

    /**
     * Show the form for creating a new sede.
     */
    public function create(): View
    {
        return view('attention::settings.sedes.create');
    }

    /**
     * Store a newly created sede.
     */
    public function store(CreateAttentionSedeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        AttentionSede::create($validated);

        return redirect()->route('settings.attention.sedes.index')
            ->with('success', 'Sede creada exitosamente.');
    }

    /**
     * Display the specified sede.
     */
    public function show(AttentionSede $sede): View
    {
        $sede->load('attentions');

        return view('attention::settings.sedes.show', [
            'sede' => $sede,
        ]);
    }

    /**
     * Show the form for editing a sede.
     */
    public function edit(AttentionSede $sede): View
    {
        return view('attention::settings.sedes.edit', [
            'sede' => $sede,
        ]);
    }

    /**
     * Update the specified sede.
     */
    public function update(UpdateAttentionSedeRequest $request, AttentionSede $sede): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $sede->update($validated);

        return redirect()->route('settings.attention.sedes.index')
            ->with('success', 'Sede actualizada exitosamente.');
    }

    /**
     * Toggle the active status of a sede.
     */
    public function toggle(AttentionSede $sede): RedirectResponse
    {
        $sede->update(['is_active' => ! $sede->is_active]);

        $status = $sede->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Sede {$status} exitosamente.");
    }

    /**
     * Bulk action on multiple sedes.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $sedes = AttentionSede::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($sedes as $sede) {
            match ($validated['action']) {
                'activate' => $sede->update(['is_active' => true]),
                'deactivate' => $sede->update(['is_active' => false]),
                'delete' => $sede->attentions()->count() === 0 ? $sede->delete() : null,
            };
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Remove the specified sede.
     */
    public function destroy(AttentionSede $sede): RedirectResponse
    {
        // Check if sede has assigned attentions
        if ($sede->attentions()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la sede porque tiene peticiones asignados.');
        }

        $sede->delete();

        return redirect()->route('settings.attention.sedes.index')
            ->with('success', 'Sede eliminada exitosamente.');
    }
}
