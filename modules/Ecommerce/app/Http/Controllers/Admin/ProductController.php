<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreProductRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateProductRequest;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Models\ProductCollection;
use Modules\Ecommerce\Models\ProductTag;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['brand', 'categories'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(20);

        return view('ecommerce::admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $brands = Brand::query()->where('status', 'published')->pluck('name', 'id');
        $categories = ProductCategory::query()->where('status', 'published')->get();
        $tags = ProductTag::query()->where('status', 'published')->pluck('name', 'id');
        $collections = ProductCollection::query()->where('status', 'published')->pluck('name', 'id');

        return view('ecommerce::admin.products.create', compact('brands', 'categories', 'tags', 'collections'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::query()->create($request->validated());
        $product->categories()->sync($request->input('categories', []));
        $product->tags()->sync($request->input('tags', []));
        $product->collections()->sync($request->input('collections', []));

        return redirect()->route('ecommerce.products.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Product $product): View
    {
        $brands = Brand::query()->where('status', 'published')->pluck('name', 'id');
        $categories = ProductCategory::query()->where('status', 'published')->get();
        $tags = ProductTag::query()->where('status', 'published')->pluck('name', 'id');
        $collections = ProductCollection::query()->where('status', 'published')->pluck('name', 'id');

        return view('ecommerce::admin.products.edit', compact('product', 'brands', 'categories', 'tags', 'collections'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());
        $product->categories()->sync($request->input('categories', []));
        $product->tags()->sync($request->input('tags', []));
        $product->collections()->sync($request->input('collections', []));

        return redirect()->route('ecommerce.products.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('ecommerce.products.index')->with('success', 'Producto eliminado exitosamente.');
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $newProduct = $product->replicate();
        $newProduct->slug = $product->slug.'-copy-'.uniqid();
        $newProduct->sku = $product->sku ? $product->sku.'-COPY-'.uniqid() : null;
        $newProduct->save();
        $newProduct->categories()->sync($product->categories->pluck('id'));
        $newProduct->tags()->sync($product->tags->pluck('id'));
        $newProduct->collections()->sync($product->collections->pluck('id'));

        return redirect()->route('ecommerce.products.index')->with('success', 'Producto duplicado exitosamente.');
    }
}
