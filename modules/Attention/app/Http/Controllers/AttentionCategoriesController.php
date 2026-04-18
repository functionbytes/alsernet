<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Attention\Http\Requests\CreateAttentionCategoryRequest;
use Modules\Attention\Http\Requests\UpdateAttentionCategoryRequest;
use Modules\Attention\Models\AttentionCategory;

class AttentionCategoriesController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): View
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

        $categories = $query->withCount('attentions')->ordered()->paginate(paginationNumber());

        $counts = AttentionCategory::query()
            ->selectRaw('COUNT(*) as total, SUM(is_active = 1) as active, SUM(is_active = 0) as inactive')
            ->first();

        $stats = [
            'total' => (int) $counts->total,
            'active' => (int) $counts->active,
            'inactive' => (int) $counts->inactive,
        ];

        return view('attention::settings.categories.index', [
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): View
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
    public function store(CreateAttentionCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
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
    public function show(AttentionCategory $category): View
    {
        $category->load('attentions');

        return view('attention::settings.categories.show', [
            'category' => $category,
        ]);
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(AttentionCategory $category): View
    {
        return view('attention::settings.categories.edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateAttentionCategoryRequest $request, AttentionCategory $category): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect()->route('settings.attention.categories.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    /**
     * Toggle the active status of a category.
     */
    public function toggle(AttentionCategory $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        $status = $category->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Categoría {$status} exitosamente.");
    }

    /**
     * Bulk action on multiple categories.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $categories = AttentionCategory::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($categories as $category) {
            match ($validated['action']) {
                'activate' => $category->update(['is_active' => true]),
                'deactivate' => $category->update(['is_active' => false]),
                'delete' => $category->attentions()->count() === 0 ? $category->delete() : null,
            };
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(AttentionCategory $category): RedirectResponse
    {
        // Check if category has assigned attentions
        if ($category->attentions()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la categoría porque tiene peticiones asignados.');
        }

        $category->delete();

        return redirect()->route('settings.attention.categories.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }
}
