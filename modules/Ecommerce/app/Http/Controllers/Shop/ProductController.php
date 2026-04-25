<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;

class ProductController extends Controller
{
    public function show(string $slug): View
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with(['brand', 'categories', 'reviews.customer'])
            ->firstOrFail();

        $relatedProducts = Product::query()
            ->where('status', 'published')
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($q) use ($product) {
                $q->whereIn('ecommerce_product_categories.id', $product->categories->pluck('id'));
            })
            ->limit(4)
            ->get();

        return view('ecommerce::shop.product', compact('product', 'relatedProducts'));
    }

    public function category(string $slug): View
    {
        $category = ProductCategory::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();
        $products = Product::query()
            ->where('status', 'published')
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('ecommerce_product_categories.id', $category->id);
            })
            ->paginate(config('ecommerce.products_per_page', 12));

        return view('ecommerce::shop.category', compact('category', 'products'));
    }

    public function brand(string $slug): View
    {
        $brand = Brand::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();
        $products = Product::query()->where('status', 'published')->where('brand_id', $brand->id)->paginate(config('ecommerce.products_per_page', 12));

        return view('ecommerce::shop.brand', compact('brand', 'products'));
    }
}
