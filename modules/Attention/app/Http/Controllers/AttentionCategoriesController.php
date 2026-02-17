<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Attention\Models\AttentionCategory;

class AttentionCategoriesController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        $query = AttentionCategory::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $categories = $query->withCount('attentions')->ordered()->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => AttentionCategory::count(),
            'active' => AttentionCategory::where('is_active', true)->count(),
            'inactive' => AttentionCategory::where('is_active', false)->count(),
        ];

        return view('attention::settings.categories.index', [
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        // Get the next order number
        $nextOrder = AttentionCategory::max('order') + 1;

        return view('attention::settings.categories.create', [
            'nextOrder' => $nextOrder,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        if (! isset($validated['order'])) {
            $validated['order'] = AttentionCategory::max('order') + 1;
        }

        AttentionCategory::create($validated);

        return redirect()->route('settings.attention.categories.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    /**
     * Display the specified category.
     */
    public function show(AttentionCategory $category)
    {
        $category->load('attentions');

        return view('attention::settings.categories.show', [
            'category' => $category,
        ]);
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(AttentionCategory $category)
    {
        return view('attention::settings.categories.edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, AttentionCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect()->route('settings.attention.categories.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    /**
     * Toggle the active status of a category.
     */
    public function toggle(AttentionCategory $category)
    {
        $category->update(['is_active' => ! $category->is_active]);

        $status = $category->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Categoría {$status} exitosamente.");
    }

    /**
     * Remove the specified category.
     */
    public function destroy(AttentionCategory $category)
    {
        // Check if category has assigned attentions
        if ($category->attentions()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la categoría porque tiene PQRSF asignados.');
        }

        $category->delete();

        return redirect()->route('settings.attention.categories.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }
}
