<?php

namespace Modules\Prestashop\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PrestaShopCategoryMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Models\Category;
use Modules\Prestashop\Services\CategorySyncService;

class CategoryController extends Controller
{
    protected ?CategorySyncService $syncService;

    public function __construct(?CategorySyncService $syncService = null)
    {
        $this->syncService = $syncService;
    }

    /**
     * Display hierarchical category tree with statistics
     */
    public function index()
    {
        // Get all categories with tree structure
        $categories = Category::with(['children' => fn ($q) => $q->ordered()])
            ->withDepth()
            ->whereNull('parent_id')
            ->ordered()
            ->get();

        // Calculate statistics
        $stats = [
            'total' => Category::count(),
            'active' => Category::active()->count(),
            'synced' => Category::syncedWithPrestaShop()->count(),
            'pending' => Category::pendingSync()->count(),
            'roots' => Category::roots()->count(),
            'leaves' => Category::leaves()->count(),
        ];

        return view('theme.views.backups.categories.index', compact('categories', 'stats'));
    }

    /**
     * Show category creation form
     */
    public function create()
    {
        // Get all categories for parent selector (excluding current and descendants)
        $parentOptions = Category::with(['ancestors'])
            ->withDepth()
            ->defaultOrder()
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => str_repeat('—', $category->depth).' '.$category->title,
                    'depth' => $category->depth,
                ];
            });

        return view('theme.views.backups.categories.create', compact('parentOptions'));
    }

    /**
     * Store new category
     */
    public function store(Request $request)
    {
        // TODO: Use StoreCategoryRequest for validation
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'slug' => 'nullable|string|unique:categories,slug',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'icon' => 'fa-duotone nullable|string',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'image' => 'nullable|image|max:2048',
            'active' => 'boolean',
            'visibility' => 'in:public,private,hidden',
            'sync_prestashop' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $validated['image'] = $path;
        }

        // Create category
        $category = Category::create($validated);

        // Auto-sync to PrestaShop if requested
        if ($request->boolean('sync_prestashop')) {
            $this->syncService->syncToPrestaShop($category);
        }

        return redirect()
            ->route('manager.backups.categories.index')
            ->with('success', 'Categoría creada exitosamente');
    }

    /**
     * Show category edit form
     */
    public function edit(string $uid)
    {
        $category = Category::where('uid', $uid)->firstOrFail();

        // Get parent options (excluding self and descendants)
        $parentOptions = Category::with(['ancestors'])
            ->withDepth()
            ->where('id', '!=', $category->id)
            ->defaultOrder()
            ->get()
            ->filter(function ($potentialParent) use ($category) {
                // Exclude descendants
                return ! $category->isAncestorOf($potentialParent);
            })
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'title' => str_repeat('—', $cat->depth).' '.$cat->title,
                    'depth' => $cat->depth,
                ];
            });

        // Get sync info
        $syncInfo = null;
        if ($category->prestashop_id) {
            $syncInfo = [
                'mapping' => $category->prestashopMapping,
                'last_synced' => $category->last_synced_at,
                'needs_sync' => $category->needs_sync,
                'is_synced' => $category->is_synced,
            ];
        }

        return view('theme.views.backups.categories.edit', compact('category', 'parentOptions', 'syncInfo'));
    }

    /**
     * Update category
     */
    public function update(Request $request, string $uid)
    {
        $category = Category::where('uid', $uid)->firstOrFail();

        // TODO: Use UpdateCategoryRequest for validation
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'slug' => 'nullable|string|unique:categories,slug,'.$category->id,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'icon' => 'fa-duotone nullable|string',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'image' => 'nullable|image|max:2048',
            'active' => 'boolean',
            'visibility' => 'in:public,private,hidden',
        ]);

        // Validate parent is not self or descendant
        if ($validated['parent_id'] && $validated['parent_id'] == $category->id) {
            return back()->withErrors(['parent_id' => 'Una categoría no puede ser su propio padre']);
        }

        if ($validated['parent_id']) {
            $newParent = Category::find($validated['parent_id']);
            if ($newParent && $category->isAncestorOf($newParent)) {
                return back()->withErrors(['parent_id' => 'No se puede mover una categoría a uno de sus descendientes']);
            }
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $validated['image'] = $path;
        }

        // Update category
        $category->update($validated);

        return redirect()
            ->route('manager.backups.categories.index')
            ->with('success', 'Categoría actualizada exitosamente');
    }

    /**
     * Delete category
     */
    public function destroy(string $uid)
    {
        $category = Category::where('uid', $uid)->firstOrFail();

        // Validate category has no children
        if ($category->children()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar una categoría que tiene subcategorías']);
        }

        // Validate category has no suppliers assigned
        if ($category->suppliers()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar una categoría que tiene proveedores asignados']);
        }

        $category->delete();

        return redirect()
            ->route('manager.backups.categories.index')
            ->with('success', 'Categoría eliminada exitosamente');
    }

    /**
     * Reorder categories via drag-drop
     */
    public function reorder(Request $request)
    {
        $items = $request->input('items', []);

        foreach ($items as $item) {
            Category::where('id', $item['id'])
                ->update([
                    'parent_id' => $item['parent_id'] ?? null,
                    'position' => $item['position'] ?? 0,
                ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Manually sync category to PrestaShop
     */
    public function syncToPrestaShop(string $uid)
    {
        $category = Category::where('uid', $uid)->firstOrFail();

        try {
            $success = $this->syncService->syncToPrestaShop($category);

            if ($success) {
                return back()->with('success', 'Categoría sincronizada a PrestaShop exitosamente');
            }

            return back()->withErrors(['error' => 'Error al sincronizar categoría a PrestaShop']);
        } catch (\Exception $e) {
            Log::error('Error syncing category to PrestaShop', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Error al sincronizar: '.$e->getMessage()]);
        }
    }

    /**
     * Import category from PrestaShop
     */
    public function syncFromPrestaShop(Request $request)
    {
        $request->validate([
            'prestashop_id' => 'required|integer',
        ]);

        try {
            $success = $this->syncService->syncFromPrestaShop($request->input('prestashop_id'));

            if ($success) {
                return back()->with('success', 'Categoría importada desde PrestaShop exitosamente');
            }

            return back()->withErrors(['error' => 'Error al importar categoría desde PrestaShop']);
        } catch (\Exception $e) {
            Log::error('Error syncing category from PrestaShop', [
                'prestashop_id' => $request->input('prestashop_id'),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Error al importar: '.$e->getMessage()]);
        }
    }

    /**
     * Display conflicts view
     */
    public function conflicts()
    {
        $mappings = PrestaShopCategoryMapping::with(['category', 'prestashopCategory'])
            ->conflicts()
            ->latestAttempts()
            ->paginate(20);

        return view('theme.views.backups.categories.conflicts', compact('mappings'));
    }

    /**
     * Resolve conflict with specified strategy
     */
    public function resolveConflict(Request $request, int $mappingId)
    {
        $request->validate([
            'strategy' => 'required|in:laravel_wins,prestashop_wins,merge,skip',
        ]);

        $mapping = PrestaShopCategoryMapping::findOrFail($mappingId);
        $strategy = $request->input('strategy');

        try {
            $success = $this->syncService->resolveConflict($mapping, $strategy);

            if ($success) {
                return back()->with('success', 'Conflicto resuelto exitosamente');
            }

            return back()->withErrors(['error' => 'Error al resolver conflicto']);
        } catch (\Exception $e) {
            Log::error('Error resolving conflict', [
                'mapping_id' => $mappingId,
                'strategy' => $strategy,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Error al resolver conflicto: '.$e->getMessage()]);
        }
    }
}
