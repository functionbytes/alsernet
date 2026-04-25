<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;

class ShopController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->where('status', 'published')
            ->with('brand')
            ->latest()
            ->paginate(config('ecommerce.products_per_page', 12));

        $categories = ProductCategory::query()->where('status', 'published')->whereNull('parent_id')->with('children')->get();
        $brands = Brand::query()->where('status', 'published')->where('is_featured', true)->get();

        return view('ecommerce::shop.index', compact('products', 'categories', 'brands'));
    }
}
