<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Attention\Models\AttentionType;

class AttentionTypesController extends Controller
{
    /**
     * Display a listing of types.
     */
    public function index(Request $request)
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

        $types = $query->withCount('attentions')->ordered()->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => AttentionType::count(),
            'active' => AttentionType::where('is_active', true)->count(),
            'inactive' => AttentionType::where('is_active', false)->count(),
        ];

        return view('attention::settings.types.index', [
            'types' => $types,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new type.
     */
    public function create()
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:attention_types,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

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
    public function show(AttentionType $type)
    {
        $type->load('attentions');

        return view('attention::settings.types.show', [
            'type' => $type,
        ]);
    }

    /**
     * Show the form for editing a type.
     */
    public function edit(AttentionType $type)
    {
        return view('attention::settings.types.edit', [
            'type' => $type,
        ]);
    }

    /**
     * Update the specified type.
     */
    public function update(Request $request, AttentionType $type)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:attention_types,code,'.$type->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $type->update($validated);

        return redirect()->route('settings.attention.types.index')
            ->with('success', 'Tipo de atención actualizado exitosamente.');
    }

    /**
     * Toggle the active status of a type.
     */
    public function toggle(AttentionType $type)
    {
        $type->update(['is_active' => ! $type->is_active]);

        $status = $type->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Tipo de atención {$status} exitosamente.");
    }

    /**
     * Reorder types.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:attention_types,id',
        ]);

        foreach ($request->order as $index => $typeId) {
            AttentionType::where('id', $typeId)->update(['order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado exitosamente.',
        ]);
    }

    /**
     * Remove the specified type.
     */
    public function destroy(AttentionType $type)
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
