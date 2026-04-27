<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Services\ProductRecommendationService;

class ShopController extends Controller
{
    public function __construct(
        private readonly ProductRecommendationService $recommendations
    ) {}

    public function index(Request $request): View
    {
        $query = Product::query()
            ->where('status', 'published')
            ->where('is_variation', false);

        if ($q = $request->input('q')) {
            $query->where(fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
        }

        if ($brandIds = $request->input('brand_ids')) {
            $query->whereIn('brand_id', (array) $brandIds);
        }

        if ($categoryIds = $request->input('category_ids')) {
            $query->whereHas('categories', fn ($c) => $c->whereIn('ecommerce_product_categories.id', (array) $categoryIds));
        }

        if ($min = $request->input('min_price')) {
            $query->where('price', '>=', $min);
        }

        if ($max = $request->input('max_price')) {
            $query->where('price', '<=', $max);
        }

        if ($request->boolean('in_stock')) {
            $query->where(fn ($q) => $q->where('with_storehouse_management', false)->orWhere('quantity', '>', 0));
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

        $products = $query->with(['brand', 'categories'])->paginate(config('ecommerce.products_per_page', 12))->withQueryString();

        $categories = ProductCategory::query()->where('status', 'published')->whereNull('parent_id')->with('children')->get();
        $brands = Brand::query()->where('status', 'published')->where('is_featured', true)->get();

        $customer = auth('ecommerce')->user();
        $suggestedProducts = $this->recommendations->suggestForCustomer($customer, 8);

        return view('ecommerce::shop.index', compact('products', 'categories', 'brands', 'suggestedProducts'));
    }
}
