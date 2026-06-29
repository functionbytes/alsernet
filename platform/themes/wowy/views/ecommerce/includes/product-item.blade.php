@if ($product instanceof \Modules\Ecommerce\Models\Product)
    <div class="product-cart-wrap mb-30">
        <div class="product-img-action-wrap">
            <div class="product-img product-img-zoom">
                <a href="{{ route('shop.product', $product->slug) }}">
                    <img class="default-img" src="{{ RvMedia::getImageUrl($product->featured_image, 'product-thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $product->name }}">
                    @php $productImages = is_array($product->images) ? $product->images : json_decode($product->images ?? '[]', true); @endphp
                    <img class="hover-img" src="{{ RvMedia::getImageUrl($productImages[1] ?? $product->featured_image, 'product-thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $product->name }}">
                </a>
            </div>
            <div class="product-action-1">
                <a aria-label="{{ __('Quick View') }}" href="#" class="action-btn hover-up js-quick-view-button" data-url="{{ route('shop.product', $product->slug) }}"><i class="fa fa-eye"></i></a>
                @if (EcommerceHelper::isWishlistEnabled())
                    <a aria-label="{{ __('Add To Wishlist') }}" href="#" class="action-btn hover-up js-add-to-wishlist-button" data-url="{{ route('ecommerce.wishlist.store', $product->id) }}"><i class="fa fa-heart"></i></a>
                @endif
            </div>
            <div class="product-badges product-badges-position product-badges-mrg">
                @php
                    $isOutOfStock = ($product->quantity ?? 0) <= 0 && $product->with_storehouse_management;
                @endphp
                @if ($isOutOfStock)
                    <span style="background-color: #000; font-size: 11px;">{{ __('Out Of Stock') }}</span>
                @else
                    @php $salePercentage = get_sale_percentage((float) $product->price, (float) $product->final_price); @endphp
                    @if ($salePercentage)
                        <span class="hot">{{ $salePercentage }}</span>
                    @endif
                @endif
            </div>
        </div>
        <div class="product-content-wrap">
            <div class="product-category">
                @php $category = $product->categories->sortByDesc('id')->first(); @endphp
                @if ($category)
                    <a href="{{ route('shop.category', $category->slug) }}">
                        <img src="{{ RvMedia::getImageUrl($category->image, 'product-thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $category->name }}">
                    </a>
                @else
                    &nbsp;
                @endif
            </div>
            <h2 class="text-truncate"><a href="{{ route('shop.product', $product->slug) }}" title="{{ $product->name }}">{{ $product->name }}</a></h2>

            @if (EcommerceHelper::isReviewEnabled())
                @php
                    $reviewsAvg = $product->reviews->avg('rating') ?? 0;
                    $reviewsCount = $product->reviews->count();
                @endphp
                <div class="rating_wrap">
                    <div class="rating">
                        <div class="product_rate" style="width: {{ $reviewsAvg * 20 }}%"></div>
                    </div>
                    <span class="rating_num">({{ $reviewsCount }})</span>
                </div>
            @endif

            {!! apply_filters('ecommerce_before_product_price_in_listing', null, $product) !!}

            <div class="product-price">
                <span>{{ format_price($product->final_price) }}</span>
                @if ($product->final_price != $product->price)
                    <span class="old-price">{{ format_price($product->price) }}</span>
                @endif
            </div>

            {!! apply_filters('ecommerce_after_product_price_in_listing', null, $product) !!}

            @if (EcommerceHelper::isCartEnabled())
                <div class="product-action-1 show" @if (!EcommerceHelper::isReviewEnabled()) style="bottom: 10px;" @endif>
                    @if ($isOutOfStock)
                        <span class="action-btn hover-up out-of-stock-btn text-danger" title="{{ __('No disponible') }}">
                            <i class="fas fa-times"></i>
                        </span>
                    @else
                        <a aria-label="{{ __('Add To Cart') }}" class="action-btn hover-up add-to-cart-button" data-id="{{ $product->id }}" data-url="{{ route('cart.add', $product->id) }}" href="#"><i class="fa fa-shopping-bag"></i></a>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif
