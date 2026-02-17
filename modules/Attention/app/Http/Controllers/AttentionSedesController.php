<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Attention\Models\AttentionSede;

class AttentionSedesController extends Controller
{
    /**
     * Display a listing of sedes.
     */
    public function index(Request $request)
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

        $sedes = $query->withCount('attentions')->orderBy('name')->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => AttentionSede::count(),
            'active' => AttentionSede::where('is_active', true)->count(),
            'inactive' => AttentionSede::where('is_active', false)->count(),
            'cities' => AttentionSede::distinct('city')->count('city'),
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
    public function create()
    {
        return view('attention::settings.sedes.create');
    }

    /**
     * Store a newly created sede.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        AttentionSede::create($validated);

        return redirect()->route('settings.attention.sedes.index')
            ->with('success', 'Sede creada exitosamente.');
    }

    /**
     * Display the specified sede.
     */
    public function show(AttentionSede $sede)
    {
        $sede->load('attentions');

        return view('attention::settings.sedes.show', [
            'sede' => $sede,
        ]);
    }

    /**
     * Show the form for editing a sede.
     */
    public function edit(AttentionSede $sede)
    {
        return view('attention::settings.sedes.edit', [
            'sede' => $sede,
        ]);
    }

    /**
     * Update the specified sede.
     */
    public function update(Request $request, AttentionSede $sede)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $sede->update($validated);

        return redirect()->route('settings.attention.sedes.index')
            ->with('success', 'Sede actualizada exitosamente.');
    }

    /**
     * Toggle the active status of a sede.
     */
    public function toggle(AttentionSede $sede)
    {
        $sede->update(['is_active' => ! $sede->is_active]);

        $status = $sede->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Sede {$status} exitosamente.");
    }

    /**
     * Remove the specified sede.
     */
    public function destroy(AttentionSede $sede)
    {
        // Check if sede has assigned attentions
        if ($sede->attentions()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la sede porque tiene PQRSF asignados.');
        }

        $sede->delete();

        return redirect()->route('settings.attention.sedes.index')
            ->with('success', 'Sede eliminada exitosamente.');
    }
}
