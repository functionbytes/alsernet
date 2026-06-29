@php
    $pcategory = $product->categories->first();
    $imagePath = $product->featured_image;
@endphp

<div class="product-cart-wrap mb-30" role="article" aria-labelledby="product-title-{{ $product->id }}">
    <div class="product-img-action-wrap">
        <div class="product-img product-img-zoom">
            <a href="{{ route('shop.product', $product->slug) }}">
                <picture>
                    <source type="image/webp"
                            srcset="{{ \Modules\Ecommerce\Supports\ProductImageHelper::srcset($imagePath, 'webp') }}"
                            sizes="(max-width: 600px) 100vw, (max-width: 1200px) 50vw, 33vw">
                    <img loading="lazy"
                         class="default-img"
                         src="{{ \Modules\Ecommerce\Supports\ProductImageHelper::url($imagePath, 'medium', 'jpg') }}"
                         srcset="{{ \Modules\Ecommerce\Supports\ProductImageHelper::srcset($imagePath, 'jpg') }}"
                         sizes="(max-width: 600px) 100vw, (max-width: 1200px) 50vw, 33vw"
                         alt="{{ $product->name }}">
                </picture>
            </a>
        </div>

        @if($product->is_on_sale)
            <div class="product-badges product-badges-position product-badges-mrg">
                <span class="hot">-{{ round((1 - $product->final_price / $product->price) * 100) }}%</span>
            </div>
        @endif

        <div class="product-action-1">
            <a aria-label="{{ __('Vista rapida') }}"
               href="#"
               class="action-btn hover-up js-quick-view"
               data-product-id="{{ $product->id }}">
                <i class="fas fa-search"></i>
            </a>
            <a aria-label="{{ __('Ver') }}" href="{{ route('shop.product', $product->slug) }}" class="action-btn hover-up">
                <i class="fas fa-eye"></i>
            </a>
            <a aria-label="{{ __('Favoritos') }}"
                href="{{ route('ecommerce.wishlist.store', $product) }}"
                class="action-btn hover-up"
                onclick="event.preventDefault(); document.getElementById('wishlist-form-{{ $product->id }}').submit();">
                <i class="fas fa-heart"></i>
            </a>
            <form id="wishlist-form-{{ $product->id }}" action="{{ route('ecommerce.wishlist.store', $product) }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>

    <div class="product-content-wrap">
        <div class="product-category">
            @if($pcategory)
                <a href="{{ route('shop.category', $pcategory->slug) }}">{{ $pcategory->name }}</a>
            @else
                &nbsp;
            @endif
        </div>

        <h2 class="text-truncate" id="product-title-{{ $product->id }}">
            <a href="{{ route('shop.product', $product->slug) }}" title="{{ $product->name }}">{{ $product->name }}</a>
        </h2>

        <div class="product-price">
            <span>${{ number_format($product->final_price, 2) }}</span>
            @if($product->is_on_sale)
                <span class="old-price">${{ number_format($product->price, 2) }}</span>
            @endif
        </div>

        <div class="product-action-1 show" style="bottom: 10px;">
            <form action="{{ route('cart.add', $product) }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="qty" value="1">
                <button type="submit" aria-label="{{ __('Agregar al carrito') }}" class="action-btn hover-up border-0 bg-transparent">
                    <i class="fas fa-shopping-bag"></i>
                </button>
            </form>
        </div>
    </div>
</div>
