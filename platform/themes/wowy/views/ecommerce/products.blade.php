@php
    $layout = theme_option('product_list_layout');

    $requestLayout = BaseHelper::stringify(request()->input('layout'));
    if ($requestLayout && in_array($requestLayout, array_keys(get_product_single_layouts()))) {
        $layout = $requestLayout;
    }

    $layout = ($layout && in_array($layout, array_keys(get_product_single_layouts()))) ? $layout : 'product-full-width';

    Theme::layout('full-width');

    Theme::asset()->usePath()->add('jquery-ui-css', 'css/plugins/jquery-ui.css');
    Theme::asset()->container('footer')->usePath()->add('jquery-ui-js', 'js/plugins/jquery-ui.js');
    Theme::asset()->container('footer')->usePath()->add('jquery-ui-touch-punch-js', 'js/plugins/jquery.ui.touch-punch.min.js');

    $products->loadMissing(['categories', 'categories.slugable']);
@endphp

<div class="container mb-30">
    <div class="row">
        @if ($layout === 'product-full-width')
            <div class="col-lg-12 m-auto mt-4">
                <a class="shop-filter-toggle mb-0" href="#">
                    <span class="fal fa-filter mr-5"></span>
                    <span class="title">{{ __('Filters') }}</span>
                    <i class="fal fa-angle-small-up angle-up"></i>
                    <i class="fal fa-angle-small-down angle-down"></i>
                </a>
                <form action="{{ URL::current() }}" method="GET" id="products-filter-ajax">
                    @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.filters')
                </form>
            </div>

            <div class="mt-4">
                <div class="products-listing position-relative">
                    @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.product-items', compact('products'))
                </div>
            </div>
        @elseif($layout === 'product-left-sidebar')
            <div class="col-xl-3 col-lg-12 col-md-12">
                <div class="widget-area">
                    @include(Theme::getThemeNamespace('views.ecommerce.includes.filters'))
                </div>
            </div>
            <div class="col-xl-9 col-lg-12 col-md-12">
                <div class="mt-4">
                    <div class="products-listing position-relative bb-product-items-wrapper">
                        @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.product-items', compact('products'))
                    </div>
                </div>
            </div>
        @elseif($layout === 'product-right-sidebar')
            <div class="col-xl-9 col-lg-12 col-md-12">
                <div class="mt-4">
                    <div class="products-listing position-relative bb-product-items-wrapper">
                        @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.product-items', compact('products'))
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-12 col-md-12">
                <div class="widget-area">
                    @include(Theme::getThemeNamespace('views.ecommerce.includes.filters'))
                </div>
            </div>
        @endif
    </div>
</div>
