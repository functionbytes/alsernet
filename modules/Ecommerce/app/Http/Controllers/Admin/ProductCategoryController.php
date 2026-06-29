<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreCategoryRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateCategoryRequest;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Supports\ProductCategoryHelper;

class ProductCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ProductCategory::query()
            ->with('parent')
            ->withCount('products')
            ->orderBy('order')
            ->paginate(50);

        $parents = ProductCategory::query()->where('status', 'published')->pluck('name', 'id');

        return view('ecommerce::categories.index', compact('categories', 'parents'));
    }

    public function create(): View
    {
        $parents = ProductCategory::query()->where('status', 'published')->pluck('name', 'id');

        return view('ecommerce::categories.create', compact('parents'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        ProductCategory::query()->create($request->validated());

        return redirect()->route('ecommerce.categories.index')->with('success', 'Categoria creada exitosamente.');
    }

    public function edit(ProductCategory $category): View
    {
        $parents = ProductCategory::query()
            ->where('status', 'published')
            ->where('id', '!=', $category->id)
            ->pluck('name', 'id');

        return view('ecommerce::categories.edit', compact('category', 'parents'));
    }

    public function update(UpdateCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('ecommerce.categories.index')->with('success', 'Categoria actualizada exitosamente.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('ecommerce.categories.index')->with('success', 'Categoria eliminada exitosamente.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($request->input('ids') as $order => $id) {
            ProductCategory::query()->where('id', $id)->update(['order' => $order]);
        }

        ProductCategoryHelper::clearCache();

        return response()->json(['ok' => true]);
    }
}
