<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\SupplierCategory\StoreSupplierCategoryRequest;
use Modules\Supplier\Http\Requests\SupplierCategory\UpdateSupplierCategoryRequest;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Traits\HasFlashMessages;

class SupplierCategoriesController extends Controller
{
    use HasFlashMessages;

    /**
     * Display supplier categories management page
     */
    public function index(string $supplierUid): View
    {
        $supplier = Supplier::byUid($supplierUid)->firstOrFail();
        $pageTitle = "Categorías: {$supplier->label}";
        $breadcrumb = 'Configuración / Proveedores / Categorías';

        // Get assigned categories with pivot data
        $assignedCategories = $supplier->categories()
            ->withPivot(['uid', 'is_active', 'priority', 'metadata', 'created_at'])
            ->orderByPivot('priority', 'desc')
            ->orderByPivot('created_at', 'desc')
            ->get();

        // Get available categories (not yet assigned)
        $assignedCategoryIds = $assignedCategories->pluck('id')->toArray();
        $availableCategories = Category::where('available', true)
            ->whereNotIn('id', $assignedCategoryIds)
            ->orderBy('name')
            ->get();

        // Get stats
        $stats = [
            'total_assigned' => $assignedCategories->count(),
            'active' => $assignedCategories->where('pivot.is_active', true)->count(),
            'inactive' => $assignedCategories->where('pivot.is_active', false)->count(),
            'total_available' => Category::where('available', true)->count(),
        ];

        return view('supplier::settings.views.categories.index', compact(
            'supplier',
            'assignedCategories',
            'availableCategories',
            'stats',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Assign a category to supplier
     */
    public function store(StoreSupplierCategoryRequest $request, string $supplierUid)
    {
        $supplier = Supplier::byUid($supplierUid)->firstOrFail();

        if ($supplier->categories()->where('category_id', $request->category_id)->exists()) {
            return $this->flashError('Esta categoría ya está asignada a este proveedor');
        }

        $supplier->categories()->attach($request->category_id, [
            'uid' => Str::ulid(),
            'is_active' => true,
            'priority' => $request->priority ?? 0,
            'metadata' => null,
        ]);

        return $this->flashSuccess('Categoría asignada exitosamente');
    }

    /**
     * Update category assignment (priority, metadata)
     */
    public function update(UpdateSupplierCategoryRequest $request, string $supplierUid, int $categoryId)
    {
        $supplier = Supplier::byUid($supplierUid)->firstOrFail();

        $supplier->categories()->updateExistingPivot($categoryId, [
            'priority' => $request->priority ?? 0,
        ]);

        return $this->flashSuccess('Prioridad actualizada exitosamente');
    }

    /**
     * Toggle category active status
     */
    public function toggle(string $supplierUid, int $categoryId)
    {
        $supplier = Supplier::byUid($supplierUid)->firstOrFail();
        $category = $supplier->categories()->where('category_id', $categoryId)->firstOrFail();

        $newStatus = ! $category->pivot->is_active;

        $supplier->categories()->updateExistingPivot($categoryId, [
            'is_active' => $newStatus,
        ]);

        return $this->flashSuccess($newStatus
            ? 'Categoría activada exitosamente'
            : 'Categoría desactivada exitosamente');
    }

    /**
     * Remove category from supplier
     */
    public function destroy(string $supplierUid, int $categoryId)
    {
        $supplier = Supplier::byUid($supplierUid)->firstOrFail();

        $hasPrompts = $supplier->prompts()
            ->where('category_id', $categoryId)
            ->exists();

        if ($hasPrompts) {
            return $this->flashError('No se puede eliminar esta categoría porque tiene prompts asociados');
        }

        $supplier->categories()->detach($categoryId);

        return $this->flashSuccess('Categoría eliminada exitosamente');
    }
}
