@php
    $layout = theme_option('product_list_layout');

    $requestLayout = request()->input('layout');
    if ($requestLayout && in_array($requestLayout, array_keys(get_product_single_layouts()))) {
        $layout = $requestLayout;
    }

    $layout = ($layout && in_array($layout, array_keys(get_product_single_layouts()))) ? $layout : 'product-full-width';

    Theme::asset()->usePath()
                    ->add('custom-scrollbar-css', 'plugins/mcustom-scrollbar/jquery.mCustomScrollbar.css');
    Theme::asset()->container('footer')->usePath()
                ->add('custom-scrollbar-js', 'plugins/mcustom-scrollbar/jquery.mCustomScrollbar.js', ['jquery']);
@endphp

@if ($layout === 'product-full-width')
    @php
        [$categories, $brands, $tags, $rand, $categoriesRequest, $urlCurrent, $categoryId, $maxFilterPrice] = EcommerceHelper::dataForFilter($category ?? null);
    @endphp

    <input type="hidden" name="layout" value="{{ $layout }}">

    <div class="shop-product-filter-header my-3" style="display: none">
        <div class="row">
            @if ($categories->isNotEmpty())
                <div class="col-lg-3 col-md-4 mb-lg-0 mb-md-5 mb-sm-5 widget-filter-item product-categories-filter-widget">
                    <h5 class="mb-20 widget__title" data-title="{{ __('Category') }}">{{ ucfirst(__('categories')) }}</h5>
                    <div class="custome-checkbox ps-custom-scrollbar">
                        <ul class="ps-list--categories">
                            @include(Theme::getThemeNamespace('views.ecommerce.includes.categories'), [
                                'categories' => $categories,
                                'activeCategoryId' => $categoryId,
                                'categoriesRequest' => $categoriesRequest,
                                'urlCurrent' => $urlCurrent
                            ])
                        </ul>
                    </div>
                </div>
            @endif

            @if ($brands->isNotEmpty())
                <div class="col-lg-3 col-md-4 mb-lg-0 mb-md-5 mb-sm-5 widget-filter-item">
                    <h5 class="mb-20 widget__title" data-title="{{ __('Brand') }}">{{ ucfirst(__('Brands')) }}</h5>
                    <div class="custome-checkbox ps-custom-scrollbar">
                        @foreach($brands as $brand)
                            <input class="form-check-input"
                                   name="brands[]"
                                   type="checkbox"
                                   id="brand-filter-{{ $brand->id }}"
                                   value="{{ $brand->id }}"
                                   @if (in_array($brand->id, (array)request()->input('brands', []))) checked @endif>
                            <label class="form-check-label" for="brand-filter-{{ $brand->id }}"><span class="d-inline-block">{{ $brand->name }}</span> <span class="d-inline-block">({{ $brand->products_count }})</span> </label>
                            <br>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($tags->isNotEmpty())
                <div class="col-lg-3 col-md-4 mb-lg-0 mb-md-5 mb-sm-5 widget-filter-item">
                    <h5 class="mb-20 widget__title" data-title="{{ __('Tag') }}">{{ ucfirst(__('tags')) }}</h5>
                    <div class="custome-checkbox ps-custom-scrollbar">
                        @foreach($tags as $tag)
                            <input class="form-check-input"
                                   name="tags[]"
                                   type="checkbox"
                                   id="tag-filter-{{ $tag->id }}"
                                   value="{{ $tag->id }}"
                                   @if (in_array($tag->id, (array)request()->input('tags', []))) checked @endif>
                            <label class="form-check-label" for="tag-filter-{{ $tag->id }}"><span class="d-inline-block">{{ $tag->name }}</span> <span class="d-inline-block">({{ $tag->products_count }})</span> </label>
                            <br>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($maxFilterPrice)
                <div class="col-lg-3 col-md-4 mb-lg-0 mb-md-5 mb-sm-5 widget-filter-item" data-type="price">
                    <h5 class="mb-20 widget__title" data-title="{{ __('Price') }}">{{ ucfirst(__('Price')) }}</h5>
                    @include(Theme::getThemeNamespace('views.ecommerce.includes.filter-by-price'))
                </div>
            @endif
        </div>

        <a class="show-advanced-filters" href="#">
            <span class="title">{{ __('Advanced filters') }}</span>
            <i class="fal fa-angle-up angle-down"></i>
            <i class="fal fa-angle-down angle-up"></i>
        </a>

        <div class="advanced-search-widgets" style="display: none">
            <div class="row">
                {!! render_product_swatches_filter([
                    'view' => Theme::getThemeNamespace() . '::views.ecommerce.attributes.attributes-filter-renderer'
                ]) !!}
            </div>
        </div>

        <div class="widget">
            <button type="submit" class="btn btn-sm btn-default"><i class="fal fa-filter mr-5 ml-0"></i> {{ __('Filter') }}</button>

            <a class="clear_filter dib clear_all_filter mx-4 btn btn-danger btn-sm" href="{{ route('public.products') }}"><i class="fal fa-refresh mr-5 ml-0"></i> {{ __('Clear All Filters') }}</a>
        </div>
    </div>
@else
    <div class="product-sidebar">
        @include('plugins/ecommerce::themes.includes.filters', ['view' => Theme::getThemeNamespace('views.ecommerce.attributes.attributes-filter-sidebar-renderer')])
    </div>
@endif
