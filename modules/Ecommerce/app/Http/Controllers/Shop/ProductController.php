<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Services\ProductRecommendationService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRecommendationService $recommendations
    ) {}

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
            ->with(['categories', 'brand'])
            ->limit(4)
            ->get();

        $frequentlyBought = $this->recommendations->frequentlyBoughtTogether($product, 4);

        return view('ecommerce::shop.product', compact('product', 'relatedProducts', 'frequentlyBought'));
    }

    public function category(Request $request, string $slug): View
    {
        $category = ProductCategory::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        $query = Product::query()
            ->where('status', 'published')
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('ecommerce_product_categories.id', $category->id);
            });

        $this->applyFilters($query, $request);

        $products = $query->with(['categories', 'brand'])->paginate(config('ecommerce.products_per_page', 12))->withQueryString();

        return view('ecommerce::shop.category', compact('category', 'products'));
    }

    public function brand(Request $request, string $slug): View
    {
        $brand = Brand::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        $query = Product::query()
            ->where('status', 'published')
            ->where('brand_id', $brand->id);

        $this->applyFilters($query, $request);

        $products = $query->with(['categories', 'brand'])->paginate(config('ecommerce.products_per_page', 12))->withQueryString();

        return view('ecommerce::shop.brand', compact('brand', 'products'));
    }

    public function quick(int $id): View
    {
        $product = Product::query()
            ->where('status', 'published')
            ->with(['categories', 'brand'])
            ->findOrFail($id);

        return view('ecommerce::shop.partials._quick-view-content', compact('product'));
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($brandIds = $request->input('brand_ids')) {
            $query->whereIn('brand_id', (array) $brandIds);
        }

        if ($categoryIds = $request->input('category_ids')) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('ecommerce_product_categories.id', (array) $categoryIds));
        }

        if ($min = $request->input('min_price')) {
            $query->where('price', '>=', $min);
        }

        if ($max = $request->input('max_price')) {
            $query->where('price', '<=', $max);
        }

        if ($request->boolean('in_stock')) {
            $query->where(function ($q) {
                $q->where('with_storehouse_management', false)->orWhere('quantity', '>', 0);
            });
        }

        if ($request->boolean('on_sale')) {
            $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price');
        }

        match ($request->input('sort', 'latest')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name_asc' => $query->orderBy('name'),
            'best_selling' => $query->orderByDesc(\DB::raw('(SELECT COALESCE(SUM(qty), 0) FROM ecommerce_order_items WHERE product_id = ecommerce_products.id)')),
            default => $query->latest(),
        };
    }
}
