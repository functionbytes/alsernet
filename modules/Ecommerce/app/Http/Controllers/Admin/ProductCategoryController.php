<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreCategoryRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateCategoryRequest;
use Modules\Ecommerce\Models\ProductCategory;

class ProductCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ProductCategory::query()
            ->with('parent')
            ->orderBy('order')
            ->paginate(20);

        return view('ecommerce::admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parents = ProductCategory::query()->where('status', 'published')->pluck('name', 'id');

        return view('ecommerce::admin.categories.create', compact('parents'));
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

        return view('ecommerce::admin.categories.edit', compact('category', 'parents'));
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
}
