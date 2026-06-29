<?php

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Blog\Models\BlogPost;
use Modules\Ecommerce\Models\FlashSale;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Template\Facades\Theme;

app()->booted(function (): void {
    // ThemeSupport no existe en inoqualabs
    // ThemeSupport::registerGoogleMapsShortcode();
    // ThemeSupport::registerYoutubeShortcode();

    // add_filter(SIMPLE_SLIDER_VIEW_TEMPLATE, function () {
    //     return Theme::getThemeNamespace().'::partials.shortcodes.sliders.main';
    // }, 120);

    add_shortcode('site-features', __('Site features'), __('Site features'), function (Shortcode $shortcode) {
        return Theme::partial('shortcodes.site-features', compact('shortcode'));
    });

    if (method_exists(shortcode_manager(), 'setAdminConfig')) {
        shortcode_manager()->setAdminConfig('site-features', function (array $attributes) {
            return Theme::partial('shortcodes.site-features-admin-config', compact('attributes'));
        });
    }

    if (is_plugin_active('ecommerce')) {
        add_shortcode(
            'featured-product-categories',
            __('Featured Product Categories'),
            __('Featured Product Categories'),
            function (Shortcode $shortcode) {
                $categories = get_featured_product_categories();

                return Theme::partial('shortcodes.featured-product-categories', [
                    'title' => $shortcode->title,
                    'description' => $shortcode->description,
                    'categories' => $categories,
                    'shortcode' => $shortcode,
                ]);
            }
        );

        if (method_exists(shortcode_manager(), 'setAdminConfig')) {
            shortcode_manager()->setAdminConfig('featured-product-categories', function (array $attributes) {
                return Theme::partial('shortcodes.featured-product-categories-admin-config', compact('attributes'));
            });
        }

        add_shortcode('featured-products', __('Featured products'), __('Featured products'), function (Shortcode $shortcode) {
            if (! is_plugin_active('ecommerce')) {
                return null;
            }

            $products = get_featured_products(array_merge([
                'take' => (int) $shortcode->limit ?: 8,
            ], EcommerceHelper::withReviewsParams()));

            return Theme::partial('shortcodes.featured-products', [
                'title' => $shortcode->title,
                'description' => $shortcode->description,
                'products' => $products,
                'shortcode' => $shortcode,
            ]);
        });

        if (method_exists(shortcode_manager(), 'setAdminConfig')) {
            shortcode_manager()->setAdminConfig('featured-products', function (array $attributes) {
                return Theme::partial('shortcodes.featured-products-admin-config', compact('attributes'));
            });
        }

        add_shortcode('flash-sale', __('Flash sale'), __('Flash sale'), function (Shortcode $shortcode) {
            $flashSales = FlashSale::query()
                ->where('status', 'published')
                ->where('end_date', '>', now())
                ->get();

            if (! $flashSales->count()) {
                return null;
            }

            $flashSale = $flashSales->first();

            if (! $flashSale || ! $flashSale->products->count()) {
                return null;
            }

            foreach ($flashSales as $item) {
                $item->load([
                    'products' => function (BelongsToMany $query) use ($shortcode) {
                        $reviewParams = EcommerceHelper::withReviewsParams();

                        if (EcommerceHelper::isReviewEnabled()) {
                            $query->withAvg($reviewParams['withAvg'][0], $reviewParams['withAvg'][1]);
                        }

                        return $query
                            ->where('status', 'published')
                            ->limit((int) $shortcode->limit ?: 2)
                            ->withCount($reviewParams['withCount'] ?? [])
                            ->with(EcommerceHelper::withProductEagerLoadingRelations());
                    },
                ]);
            }

            return Theme::partial('shortcodes.flash-sale', [
                'title' => $shortcode->title,
                'showPopup' => $shortcode->show_popup,
                'flashSale' => $flashSale,
                'flashSales' => $flashSales,
            ]);
        });

        if (method_exists(shortcode_manager(), 'setAdminConfig')) {
            shortcode_manager()->setAdminConfig('flash-sale', function (array $attributes) {
                return Theme::partial('shortcodes.flash-sale-admin-config', compact('attributes'));
            });
        }

        add_shortcode(
            'product-collections',
            __('Product Collections'),
            __('Product Collections'),
            function (Shortcode $shortcode) {
                $productCollections = get_product_collections(
                    ['status' => 'published'],
                    [],
                    ['id', 'name', 'slug']
                );

                if ($productCollections->isEmpty()) {
                    return null;
                }

                $limit = (int) $shortcode->limit ?: 8;

                $products = get_products_by_collections(array_merge([
                    'collections' => [
                        'by' => 'id',
                        'value_in' => [$productCollections->first()->id],
                    ],
                    'take' => $limit,
                    'with' => EcommerceHelper::withProductEagerLoadingRelations(),
                ], EcommerceHelper::withReviewsParams()));

                return Theme::partial('shortcodes.product-collections', [
                    'title' => $shortcode->title,
                    'productCollections' => $productCollections,
                    'limit' => $limit,
                    'products' => $products,
                ]);
            }
        );

        if (method_exists(shortcode_manager(), 'setAdminConfig')) {
            shortcode_manager()->setAdminConfig('product-collections', function (array $attributes) {
                return Theme::partial('shortcodes.product-collections-admin-config', compact('attributes'));
            });
        }

        add_shortcode(
            'product-category-products',
            __('Product category products'),
            __('Product category products'),
            function (Shortcode $shortcode) {
                $category = ProductCategory::query()
                    ->where('status', 'published')
                    ->where('id', (int) $shortcode->category_id)
                    ->with([
                        'children' => function ($query) {
                            return $query->limit(3);
                        },
                    ])
                    ->first();

                if (! $category) {
                    return null;
                }

                $limit = (int) $shortcode->limit ?: 8;

                $categoryIds = array_merge([$category->id], $category->children->pluck('id')->all());

                $products = get_products_by_categories(array_merge([
                    'categories' => [
                        'by' => 'id',
                        'value_in' => $categoryIds,
                    ],
                    'take' => $limit,
                ], EcommerceHelper::withReviewsParams()));

                return Theme::partial('shortcodes.product-category-products', compact('category', 'products', 'limit'));
            }
        );

        if (method_exists(shortcode_manager(), 'setAdminConfig')) {
            shortcode_manager()->setAdminConfig('product-category-products', function (array $attributes) {
                $categories = ProductCategory::query()
                    ->where('status', 'published')
                    ->pluck('name', 'id');

                return Theme::partial(
                    'shortcodes.product-category-products-admin-config',
                    compact('categories', 'attributes')
                );
            });
        }

        add_shortcode('featured-brands', __('Featured Brands'), __('Featured Brands'), function (Shortcode $shortcode) {
            $brands = get_featured_brands();

            return Theme::partial('shortcodes.featured-brands', [
                'title' => $shortcode->title,
                'brands' => $brands,
                'shortcode' => $shortcode,
            ]);
        });

        if (method_exists(shortcode_manager(), 'setAdminConfig')) {
            shortcode_manager()->setAdminConfig('featured-brands', function (array $attributes) {
                return Theme::partial('shortcodes.featured-brands-admin-config', compact('attributes'));
            });
        }
    }

    // Módulo Ads no existe en inoqualabs — el bloque completo se omite
    // if (is_plugin_active('ads')) { ... }

    if (is_plugin_active('blog')) {
        add_shortcode('featured-news', __('Featured News'), __('Featured News'), function (Shortcode $shortcode) {
            $posts = BlogPost::query()
                ->where('status', 'published')
                ->where('is_featured', true)
                ->limit(4)
                ->with(['categories'])
                ->get();

            return Theme::partial('shortcodes.featured-news', ['title' => $shortcode->title, 'posts' => $posts]);
        });

        if (method_exists(shortcode_manager(), 'setAdminConfig')) {
            shortcode_manager()->setAdminConfig('featured-news', function (array $attributes) {
                return Theme::partial('shortcodes.featured-news-admin-config', compact('attributes'));
            });
        }
    }

    if (is_plugin_active('contact')) {
        // add_filter(CONTACT_FORM_TEMPLATE_VIEW, function () {
        //     return Theme::getThemeNamespace().'::partials.shortcodes.contact-form';
        // }, 120);
    }

    if (is_plugin_active('newsletter')) {
        add_shortcode('newsletter-form', __('Newsletter Form'), __('Newsletter Form'), function (Shortcode $shortcode) {
            return Theme::partial('shortcodes.newsletter-form', [
                'title' => $shortcode->title,
                'description' => $shortcode->description,
            ]);
        });

        if (method_exists(shortcode_manager(), 'setAdminConfig')) {
            shortcode_manager()->setAdminConfig('newsletter-form', function (array $attributes) {
                return Theme::partial('shortcodes.newsletter-form-admin-config', compact('attributes'));
            });
        }

        add_shortcode('newsletter-inline', __('Newsletter Inline'), __('Newsletter Inline Subscription'), function (Shortcode $shortcode) {
            return Theme::partial('shortcodes.newsletter-inline', [
                'shortcode' => $shortcode,
            ]);
        });

        if (method_exists(shortcode_manager(), 'setAdminConfig')) {
            shortcode_manager()->setAdminConfig('newsletter-inline', function (array $attributes) {
                return Theme::partial('shortcodes.newsletter-inline-admin-config', compact('attributes'));
            });
        }
    }

    add_shortcode('our-offices', __('Our offices'), __('Our offices'), function () {
        return Theme::partial('shortcodes.our-offices');
    });

    if (method_exists(shortcode_manager(), 'setAdminConfig')) {
        shortcode_manager()->setAdminConfig('our-offices', function (array $attributes) {
            return Theme::partial('shortcodes.our-offices-admin-config', compact('attributes'));
        });
    }

    // Módulo FAQ no existe en inoqualabs — el bloque completo se omite
    // if (is_plugin_active('faq')) { ... }
});
