<link rel="stylesheet" href="{{ Theme::asset()->url('plugins/lightGallery/css/lightgallery.min.css') }}">
<script src="{{ Theme::asset()->url('plugins/lightGallery/js/lightgallery.min.js') }}"></script>

<div class="row">
    <div class="col-md-7 col-sm-12 col-xs-12">
        <div class="detail-gallery">
            <span class="zoom-icon"><i class="fi-rs-search"></i></span>
            <div class="product-image-slider">
                @foreach ($productImages as $img)
                    <figure class="border-radius-10">
                        <a href="{{ RvMedia::getImageUrl($img) }}"><img src="{{ RvMedia::getImageUrl($img, 'medium') }}" alt="{{ $product->name }}"></a>
                    </figure>
                @endforeach
            </div>
        </div>
     </div>
    <div class="col-md-5 col-sm-12 col-xs-12">
        <div class="detail-info">
            <h3 class="title-detail mt-10"><a href="{{ $product->url }}">{{ $product->name }}</a></h3>
            <div class="product-detail-rating">

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
            <div class="short-desc mb-10">
                <p class="font-sm">
                    {!! apply_filters('ecommerce_before_product_description', null, $product) !!}
                    {!! BaseHelper::clean($product->description) !!}
                    {!! apply_filters('ecommerce_after_product_description', null, $product) !!}
                </p>
            </div>

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

            @if ($product->options()->count() > 0 && isset($product->toArray()['options']))
                <div class="pr_switch_wrap" id="product-option">
                    {!! render_product_options($product) !!}
                </div>
            @endif

            <form class="add-to-cart-form" method="POST" action="{{ route('public.cart.add-to-cart') }}">
                @csrf
                {!! apply_filters(ECOMMERCE_PRODUCT_DETAIL_EXTRA_HTML, null, $product) !!}
                <input type="hidden" name="id" class="hidden-product-id" value="{{ ($product->is_variation || !$product->defaultVariation->product_id) ? $product->id : $product->defaultVariation->product_id }}"/>
                <div class="detail-extralink">
                    @if (EcommerceHelper::isCartEnabled())
                        <div class="detail-qty border radius">
                            <a href="#" class="qty-down"><i class="fa fa-caret-down" aria-hidden="true"></i></a>
                            <input type="number" min="1" value="1" name="qty" class="qty-val qty-input" />
                            <a href="#" class="qty-up"><i class="fa fa-caret-up" aria-hidden="true"></i></a>
                        </div>
                    @endif

                    <div class="product-extra-link2">
                        @if (EcommerceHelper::isCartEnabled())
                            <button type="submit" class="button button-add-to-cart @if ($product->isOutOfStock()) btn-disabled @endif" type="submit" @if ($product->isOutOfStock()) disabled @endif>{{ __('Add to cart') }}</button>
                        @endif
                        @if (EcommerceHelper::isWishlistEnabled())
                            <a aria-label="{{ __('Add To Wishlist') }}" class="action-btn hover-up js-add-to-wishlist-button" data-url="{{ route('public.wishlist.add', $product->id) }}" href="#"><i class="far fa-heart"></i></a>
                        @endif
                    </div>
                </div>
            </form>
            <ul class="product-meta font-xs color-grey">

                @if ($product->categories->count())
                    <li class="mb-5"><span class="d-inline-block">{{ __('Categories') }}:</span>
                        @foreach($product->categories as $category)
                            <a href="{{ $category->url }}" title="{{ $category->name }}">{{ $category->name }}</a>@if (!$loop->last),@endif
                        @endforeach
                    </li>
                @endif

                <li><span class="d-inline-block">{{ __('Availability') }}:</span> <span class="in-stock text-success ml-5">{!! BaseHelper::clean($product->stock_status_html) !!}</span></li>
            </ul>
        </div>
        <!-- Detail Info -->
    </div>
</div>
