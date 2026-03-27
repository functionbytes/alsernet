<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Attention\Http\Requests\CreateAttentionTypeRequest;
use Modules\Attention\Http\Requests\UpdateAttentionTypeRequest;
use Modules\Attention\Models\AttentionType;

class AttentionTypesController extends Controller
{
    /**
     * Display a listing of types.
     */
    public function index(Request $request): View
    {
        $query = AttentionType::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $types = $query->withCount('attentions')->ordered()->paginate(paginationNumber());

        $counts = AttentionType::query()
            ->selectRaw('COUNT(*) as total, SUM(is_active = 1) as active, SUM(is_active = 0) as inactive')
            ->first();

        $stats = [
            'total' => (int) $counts->total,
            'active' => (int) $counts->active,
            'inactive' => (int) $counts->inactive,
        ];

        return view('attention::settings.types.index', [
            'types' => $types,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new type.
     */
    public function create(): View
    {
        // Get the next order number
        $nextOrder = AttentionType::max('order') + 1;

        return view('attention::settings.types.create', [
            'nextOrder' => $nextOrder,
        ]);
    }

    /**
     * Store a newly created type.
     */
    public function store(CreateAttentionTypeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        if (! isset($validated['order'])) {
            $validated['order'] = AttentionType::max('order') + 1;
        }

        AttentionType::create($validated);

        return redirect()->route('settings.attention.types.index')
            ->with('success', 'Tipo de atención creado exitosamente.');
    }

    /**
     * Display the specified type.
     */
    public function show(AttentionType $type): View
    {
        $type->load('attentions');

        return view('attention::settings.types.show', [
            'type' => $type,
        ]);
    }

    /**
     * Show the form for editing a type.
     */
    public function edit(AttentionType $type): View
    {
        return view('attention::settings.types.edit', [
            'type' => $type,
        ]);
    }

    /**
     * Update the specified type.
     */
    public function update(UpdateAttentionTypeRequest $request, AttentionType $type): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $type->update($validated);

        return redirect()->route('settings.attention.types.index')
            ->with('success', 'Tipo de atención actualizado exitosamente.');
    }

    /**
     * Toggle the active status of a type.
     */
    public function toggle(AttentionType $type): RedirectResponse
    {
        $type->update(['is_active' => ! $type->is_active]);

        $status = $type->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Tipo de atención {$status} exitosamente.");
    }

    /**
     * Reorder types.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:attention_types,id',
        ]);

        $cases = collect($request->order)
            ->map(fn ($id, $i) => 'WHEN '.(int) $id.' THEN '.($i + 1))
            ->join(' ');

        AttentionType::whereIn('id', $request->order)
            ->update(['order' => DB::raw("CASE id {$cases} END")]);

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado exitosamente.',
        ]);
    }

    /**
     * Remove the specified type.
     */
    public function destroy(AttentionType $type): RedirectResponse
    {
        // Check if type has assigned attentions
        if ($type->attentions()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el tipo porque tiene PQRSF asignados.');
        }

        $type->delete();

        return redirect()->route('settings.attention.types.index')
            ->with('success', 'Tipo de atención eliminado exitosamente.');
    }
}
