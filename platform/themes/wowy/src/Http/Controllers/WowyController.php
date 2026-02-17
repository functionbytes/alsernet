<?php

namespace Theme\Wowy\Http\Controllers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Repositories\Interfaces\ProductCategoryInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductInterface;
use Botble\Ecommerce\Services\Products\GetProductService;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Http\Controllers\PublicController;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class WowyController extends PublicController
{
    public function ajaxCart(Request $request, BaseHttpResponse $response)
    {
        if (! $request->ajax()) {
            return $response->setNextUrl(BaseHelper::getHomepageUrl());
        }

        return $response->setData([
            'count' => Cart::instance('cart')->count(),
            'html' => Theme::partial('cart-panel'),
        ]);
    }

    public function getQuickView(int|string $id, Request $request, BaseHttpResponse $response)
    {
        if (! $request->ajax()) {
            return $response->setNextUrl(BaseHelper::getHomepageUrl());
        }

        $product = get_products(
            array_merge([
                'condition' => [
                    'ec_products.id' => $id,
                    'ec_products.status' => BaseStatusEnum::PUBLISHED,
                ],
                'take' => 1,
                'with' => [
                    'slugable',
                    'tags',
                    'tags.slugable',
                    'options' => function ($query) {
                        return $query->with('values');
                    },
                ],
            ], EcommerceHelper::withReviewsParams())
        );

        if (! $product) {
            return $response->setNextUrl(BaseHelper::getHomepageUrl());
        }

        [$productImages, , $selectedAttrs] = EcommerceHelper::getProductVariationInfo($product);

        return $response->setData(Theme::partial('quick-view', compact('product', 'selectedAttrs', 'productImages')));
    }

    public function ajaxGetProductsByCollection(int|string $id, Request $request, BaseHttpResponse $response)
    {
        if (! $request->expectsJson()) {
            return $response->setNextUrl(BaseHelper::getHomepageUrl());
        }

        $products = get_products_by_collections(array_merge([
            'collections' => [
                'by' => 'id',
                'value_in' => [$id],
            ],
            'take' => $request->integer('limit') ?: 8,
            'with' => EcommerceHelper::withProductEagerLoadingRelations(),
        ], EcommerceHelper::withReviewsParams()));

        $data = [];
        foreach ($products as $product) {
            $data[] = '<div class="col-lg-3 col-md-4 col-sm-6 col-' . (theme_option('number_of_products_per_row_on_mobile', 2) == 2 ? 6 : 12) . '">' . view(
                Theme::getThemeNamespace() . '::views.ecommerce.includes.product-item',
                compact('product')
            )->render() . '</div>';
        }

        return $response->setData($data);
    }

    public function ajaxGetProductsByCategory(
        int|string $id,
        Request $request,
        BaseHttpResponse $response,
        ProductInterface $productRepository,
        ProductCategoryInterface $productCategoryRepository
    ) {
        if (! $request->expectsJson()) {
            return $response->setNextUrl(BaseHelper::getHomepageUrl());
        }

        $category = $productCategoryRepository->getFirstBy(
            [
                'status' => BaseStatusEnum::PUBLISHED,
                'id' => $id,
            ],
            ['*'],
            [
                'activeChildren' => function ($query) {
                    return $query->limit(3);
                },
            ]
        );

        if (! $category) {
            return $response->setData([]);
        }

        $products = $productRepository->getProductsByCategories(array_merge([
            'categories' => [
                'by' => 'id',
                'value_in' => array_merge([$category->id], $category->activeChildren->pluck('id')->all()),
            ],
            'take' => $request->integer('limit') ?: 8,
        ], EcommerceHelper::withReviewsParams()));

        $data = [];
        foreach ($products as $product) {
            $data[] = '<div class="col-lg-3 col-md-4 col-sm-6 col-' . (theme_option('number_of_products_per_row_on_mobile', 2) == 2 ? 6 : 12) . '">' . view(
                Theme::getThemeNamespace() . '::views.ecommerce.includes.product-item',
                compact('product')
            )->render() . '</div>';
        }

        return $response->setData($data);
    }

    public function ajaxSearchProducts(
        Request $request,
        GetProductService $productService,
        BaseHttpResponse $response
    ) {
        if (! $request->expectsJson()) {
            return $response->setNextUrl(BaseHelper::getHomepageUrl());
        }

        $request->merge(['num' => 12]);

        $products = $productService->getProduct($request);

        $queries = $request->input();
        foreach ($queries as $key => $query) {
            if (! $query || $key == 'num' || (is_array($query) && ! Arr::get($query, 0))) {
                unset($queries[$key]);
            }
        }

        $total = $products->count();
        $message = $total != 1 ? __(':total Products found', compact('total')) : __(':total Product found', compact('total'));

        return $response
            ->setData(Theme::partial('ajax-search-results', compact('products', 'queries')))
            ->setMessage($message);
    }
}
