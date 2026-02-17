@php
    $layout = MetaBox::getMetaData($product, 'layout', true);
    $layout = ($layout && in_array($layout, array_keys(get_product_single_layouts()))) ? $layout : 'product-full-width';
    Theme::layout($layout);

    Theme::asset()->usePath()->add('lightGallery-css', 'plugins/lightGallery/css/lightgallery.min.css');
    Theme::asset()->container('footer')->usePath()->add('lightGallery-js', 'plugins/lightGallery/js/lightgallery.min.js', ['jquery']);
@endphp

<div class="product-detail accordion-detail">
    <div class="row mb-50">
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="detail-gallery">
                <div class="product-image-slider">
                    @foreach ($productImages as $img)
                        <figure class="border-radius-10">
                            <a href="{{ RvMedia::getImageUrl($img) }}">
                                <img src="{{ RvMedia::getImageUrl($img, 'medium') }}" alt="{{ $product->name }}">
                            </a>
                        </figure>
                    @endforeach
                </div>

            </div>
           </div>
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="detail-info">
                <h2 class="title-detail">{{ $product->name }}</h2>
                <div class="product-detail-rating">
                    @if ($product->brand->id)
                        <div class="pro-details-brand">
                            <span class="d-inline-block me-1">{{ __('Brands') }}:</span> <a href="{{ $product->brand->url }}">{{ $product->brand->name }}</a>
                        </div>
                    @endif

                    @if (EcommerceHelper::isReviewEnabled())
                        <div class="product-rate-cover text-end">
                            <div class="rating_wrap">
                                <div class="rating">
                                    <div class="product_rate" style="width: {{ $product->reviews_avg * 20 }}%"></div>
                                </div>
                                <span class="rating_num">({{ __(':count reviews', ['count' => $product->reviews_count]) }})</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="clearfix product-price-cover">
                    <div class="product-price primary-color float-left">
                        <ins><span class="text-brand">{{ format_price($product->front_sale_price_with_taxes) }}</span></ins>
                        @if ($product->front_sale_price !== $product->price)
                            <ins><span class="old-price font-md ml-15">{{ format_price($product->price_with_taxes) }}</span></ins>
                            <span class="save-price font-md color3 ml-15"><span class="percentage-off d-inline-block">{{ get_sale_percentage($product->price, $product->front_sale_price) }}</span> <span class="d-inline-block">{{ __('Off') }}</span></span>
                        @endif
                    </div>
                </div>
                <div class="bt-1 border-color-1 mt-15 mb-15"></div>
                <div class="short-desc ">
                    {!! apply_filters('ecommerce_before_product_description', null, $product) !!}
                    {!! BaseHelper::clean($product->description) !!}
                    {!! apply_filters('ecommerce_after_product_description', null, $product) !!}
                </div>

                <div class="bt-1 border-color-1 mt-15 mb-15"></div>
                <div class="hr-section"></div>


                <form class="add-to-cart-form" method="POST" action="{{ route('public.cart.add-to-cart') }}">
                    @csrf

                    @if ($product->variations()->count() > 0)
                        <div class="pr_switch_wrap">
                            {!! render_product_swatches($product, [
                                'selected' => $selectedAttrs,
                                'view'     => Theme::getThemeNamespace() . '::views.ecommerce.attributes.swatches-renderer'
                            ]) !!}
                        </div>
                        <div class="number-items-available" style="@if (!$product->isOutOfStock()) display: none; @endif margin-bottom: 10px;">
                            @if ($product->isOutOfStock())
                                <span class="text-danger">({{ __('Out of stock') }})</span>
                            @endif
                        </div>
                    @endif

                    {!! render_product_options($product) !!}

                    {!! apply_filters(ECOMMERCE_PRODUCT_DETAIL_EXTRA_HTML, null, $product) !!}
                    <input type="hidden" name="id" class="hidden-product-id" value="{{ ($product->is_variation || !$product->defaultVariation->product_id) ? $product->id : $product->defaultVariation->product_id }}"/>

                    <div class="row mt-10 mb-10"></div>





                    @if (EcommerceHelper::isCartEnabled())

                            @if ($product->isOutOfStock())
                                <div class="detail-extralink out-of-stock-message">
                                    <h3>{{ __('Out Of Stock') }}</h3>

                                    <div class="out-of-stock-container">

                                        <p> {{ __('We are working to restock it as soon as possible.') }}</p>
                                        <p> {{ __('If you would like more information about this product or its restock, please contact us by email.') }}
                                            <a href="mailto:{{ theme_option('contact_email') }}">{{ theme_option('contact_email') }}</a>
                                            {{ __('or to our WhatsApp') }}
                                            <a href="https://wa.me/{{ theme_option('phone') }}" target="_blank" rel="noopener">{{ theme_option('phone') }}</a>.
                                        </p>
                                    </div>

                                </div>
                            @else

                            <div class="detail-extralink">
                                @if (EcommerceHelper::isCartEnabled() && !$product->isOutOfStock())
                                    <div class="detail-qty border radius">
                                        <a href="#" class="qty-down"><i class="fa fa-caret-down" aria-hidden="true"></i></a>
                                        <input type="number" min="1" value="1" name="qty" class="qty-val qty-input" />
                                        <a href="#" class="qty-up"><i class="fa fa-caret-up" aria-hidden="true"></i></a>
                                    </div>
                                @endif

                                <div class="product-extra-link2 @if (EcommerceHelper::isQuickBuyButtonEnabled()) has-buy-now-button @endif">

                                    <button type="submit" class="button button-add-to-cart" type="submit">{{ __('Add to cart') }}</button>
                                    @if (EcommerceHelper::isQuickBuyButtonEnabled())
                                        <button class="button button-buy-now " type="submit" name="checkout">{{ __('Buy Now') }}</button>
                                    @endif

                                    @if (EcommerceHelper::isWishlistEnabled())
                                        <a aria-label="{{ __('Add To Wishlist') }}" class="action-btn hover-up js-add-to-wishlist-button" data-url="{{ route('public.wishlist.add', $product->id) }}" href="#"><i class="far fa-heart"></i></a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif


                </form>
                <ul class="product-meta font-xs color-grey mt-30">

                    <li class="mb-5 @if (! $product->sku) d-none @endif"><span class="d-inline-block me-1" id="product-sku">{{ __('SKU') }}</span>: <span>{{ $product->sku }}</span></li>

                    @if ($product->categories->isNotEmpty())
                        <li class="mb-5"><span class="d-inline-block me-1">{{ __('Categories') }}:</span>
                        @foreach($product->categories as $category)
                            <a href="{{ $category->url }}" title="{{ $category->name }}">{{ $category->name }}</a>@if (!$loop->last),@endif
                        @endforeach
                    </li>
                    @endif
                    @if ($product->tags->isNotEmpty())
                        <li class="mb-5"><span class="d-inline-block me-1">{{ __('Tags') }}:</span>
                        @foreach($product->tags as $tag)
                            <a href="{{ $tag->url }}" rel="tag" title="{{ $tag->name }}">{{ $tag->name }}</a>@if (!$loop->last),@endif
                        @endforeach
                        </li>
                    @endif

                    <li><span class="d-inline-block me-1">{{ __('Availability') }}:</span> <span class="in-stock text-success ml-5">{!! BaseHelper::clean($product->stock_status_html) !!}</span></li>
                </ul>
            </div>
            <!-- Detail Info -->

        </div>
    </div>
    <div class="tab-style3">
        <ul class="nav nav-tabs text-uppercase">
            <li class="nav-item">
                <a class="nav-link active" id="Description-tab" data-bs-toggle="tab" href="#Description">{{ __('Description') }}</a>
            </li>
            @if (EcommerceHelper::isProductSpecificationEnabled() && $product->specificationAttributes->where('pivot.hidden', false)->isNotEmpty())
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-specification">{{ __('Specification') }}</a></li>
            @endif
            @if (EcommerceHelper::isReviewEnabled())
                <li class="nav-item">
                    <a class="nav-link" id="Reviews-tab" data-bs-toggle="tab" href="#Reviews">{{ __('Reviews') }} ({{ $product->reviews_count }})</a>
                </li>
            @endif
            @if (is_plugin_active('faq'))
                @if (count($product->faq_items) > 0)
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-faq">{{ __('Questions and Answers') }}</a>
                    </li>
                @endif
            @endif
        </ul>
        <div class="tab-content shop_info_tab entry-main-content">
            <div class="tab-pane fade show active" id="Description">
                <div class="ck-content">
                    {!! BaseHelper::clean($product->content) !!}
                </div>
                {!! apply_filters(BASE_FILTER_PUBLIC_COMMENT_AREA, null, $product) !!}
            </div>

            @if (EcommerceHelper::isProductSpecificationEnabled() && $product->specificationAttributes->where('pivot.hidden', false)->isNotEmpty())
                <div class="tab-pane fade" id="tab-specification">
                    <div class="tp-product-details-additional-info">
                        @include(EcommerceHelper::viewPath('includes.product-specification'))
                    </div>
                </div>
            @endif

            @if (is_plugin_active('faq') && count($product->faq_items) > 0)
                <div class="tab-pane fade faqs-list" id="tab-faq">
                    <div class="accordion" id="faq-accordion">
                        @foreach($product->faq_items as $faq)
                            <div class="card">
                                <div class="card-header" id="heading-faq-{{ $loop->index }}">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left @if (!$loop->first) collapsed @endif" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-faq-{{ $loop->index }}" aria-expanded="true" aria-controls="collapse-faq-{{ $loop->index }}">
                                            {!! BaseHelper::clean($faq[0]['value']) !!}
                                        </button>
                                    </h2>
                                </div>

                                <div id="collapse-faq-{{ $loop->index }}" class="collapse @if ($loop->first) show @endif" aria-labelledby="heading-faq-{{ $loop->index }}" data-parent="#faq-accordion">
                                    <div class="card-body">
                                        {!! BaseHelper::clean($faq[1]['value']) !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (EcommerceHelper::isReviewEnabled())
                <div class="tab-pane fade" id="Reviews">
                    @include('plugins/ecommerce::themes.includes.reviews', ['reviewButtonClass' => 'button'])
                </div>
            @endif
        </div>
    </div>

    @php
        $crossSellProducts = get_cross_sale_products($product, $layout == 'product-full-width' ? 4 : 3);
    @endphp
    @if (count($crossSellProducts) > 0)
        <div class="row mt-60">
            <div class="col-12">
                <h3 class="section-title style-1 mb-30">{{ __('You may also like') }}</h3>
            </div>
            @foreach($crossSellProducts as $crossProduct)
                <div class="col-lg-{{ 12 / ($layout == 'product-full-width' ? 4 : 3) }} col-md-4 col-12 col-sm-6">
                    @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.product-item', ['product' => $crossProduct])
                </div>
            @endforeach
        </div>
    @endif

    @php
        $relatedProducts = get_related_products($product, 4);
    @endphp

    @if (count($relatedProducts) > 0)
        <div class="row mt-60" id="related-products">
            <div class="col-12">
                <h3 class="section-title style-1 mb-30">{{ __('Related products') }}</h3>
            </div>
            @foreach($relatedProducts as $relatedProduct)
                <div class="col-lg-{{ 12 / ($layout == 'product-full-width' ? 4 : 3) }} col-md-4 col-12 col-sm-6">
                    @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.product-item', ['product' => $relatedProduct])
                </div>
            @endforeach
        </div>
    @endif
</div>
