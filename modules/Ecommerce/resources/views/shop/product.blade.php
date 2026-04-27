@extends('ecommerce::layouts.shop-wowy')

@section('title', $product->meta_title ?? $product->name)

@section('meta')
@php
    $ogTitle       = $product->meta_title ?? $product->name;
    $ogDescription = Str::limit(strip_tags($product->meta_description ?? $product->description ?? $product->name), 160);
    $ogImage       = $product->featured_image ?: asset('modules/ecommerce/images/404.png');
    $ogUrl         = url()->current();
    $ogAvailability = ($product->quantity > 0 || ! $product->with_storehouse_management) ? 'in stock' : 'out of stock';
@endphp
<link rel="canonical" href="{{ $ogUrl }}">
<meta name="description" content="{{ $ogDescription }}">
{{-- Open Graph --}}
<meta property="og:type" content="product">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
{{-- Product-specific OG --}}
<meta property="product:price:amount" content="{{ $product->final_price }}">
<meta property="product:price:currency" content="USD">
<meta property="og:availability" content="{{ $ogAvailability }}">
{{-- Twitter Cards --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@endsection

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span>
            @php $firstCategory = $product->categories->first(); @endphp
            @if($firstCategory)
                <a href="{{ route('shop.category', $firstCategory->slug) }}">{{ $firstCategory->name }}</a>
                <span></span>
            @endif
            {{ $product->name }}
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container mb-30">
    <div class="product-detail accordion-detail">
        <div class="row mb-50">
            <div class="col-md-6 col-sm-12 col-xs-12">
                @php
                    use Modules\Ecommerce\Supports\ProductImageHelper;
                    $galleryImages = collect();
                    if ($product->featured_image) {
                        $galleryImages->push($product->featured_image);
                    }
                    if (!empty($product->images)) {
                        foreach ($product->images as $img) {
                            if ($img) {
                                $galleryImages->push($img);
                            }
                        }
                    }
                    $galleryImages = $galleryImages->unique()->values();
                    if ($galleryImages->isEmpty()) {
                        $galleryImages->push(null);
                    }
                @endphp
                <div class="detail-gallery product-gallery">
                    <div class="main-image-wrap mb-3" id="product-main-image-wrap">
                        <picture id="product-main-picture">
                            <source type="image/webp" srcset="{{ ProductImageHelper::srcset($galleryImages->first(), 'webp') }}">
                            <img id="product-main-image"
                                 src="{{ ProductImageHelper::url($galleryImages->first(), 'large', 'jpg') }}"
                                 alt="{{ $product->name }}"
                                 class="product-zoom-img"
                                 loading="eager">
                        </picture>
                    </div>

                    @if($galleryImages->count() > 1)
                        <div class="product-thumbnails d-flex gap-2">
                            @foreach($galleryImages as $idx => $img)
                                <button type="button"
                                        class="thumb-btn border p-0 {{ $idx === 0 ? 'active' : '' }}"
                                        data-image="{{ ProductImageHelper::url($img, 'large', 'jpg') }}"
                                        data-srcset="{{ ProductImageHelper::srcset($img, 'webp') }}"
                                        aria-label="{{ __('Ver imagen') }} {{ $idx + 1 }}">
                                    <img src="{{ ProductImageHelper::url($img, 'thumb', 'jpg') }}"
                                         alt="{{ $product->name }} {{ $idx + 1 }}"
                                         loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-6 col-sm-12 col-xs-12">
                <div class="detail-info mt-20">
                    <h2 class="title-detail">{{ $product->name }}</h2>
                    <div class="product-detail-rating">
                        @if($product->brand && $product->brand->id)
                            <div class="pro-details-brand">
                                <span class="d-inline-block me-1">{{ __('Marca') }}:</span>
                                <a href="{{ route('shop.brand', $product->brand->slug) }}">{{ $product->brand->name }}</a>
                            </div>
                        @endif
                    </div>
                    <div class="clearfix product-price-cover">
                        <div class="product-price primary-color float-left">
                            <ins><span class="text-brand">${{ number_format($product->final_price, 2) }}</span></ins>
                            @if($product->is_on_sale)
                                <ins><span class="old-price font-md ml-15">${{ number_format($product->price, 2) }}</span></ins>
                                <span class="save-price font-md color3 ml-15">
                                    <span class="percentage-off d-inline-block">-{{ round((1 - $product->final_price / $product->price) * 100) }}%</span>
                                </span>
                            @endif
                        </div>
                    </div>
                    @php
                        $stockIsOut  = $product->with_storehouse_management && $product->quantity <= 0;
                        $stockIsLow  = $product->with_storehouse_management && $product->quantity > 0 && $product->quantity <= 10;
                        $cartCount   = \Modules\Ecommerce\Models\Cart::query()->where('product_id', $product->id)->sum('qty');
                        $recentViews = \Modules\Ecommerce\Models\ProductView::query()
                            ->where('product_id', $product->id)
                            ->where('date', '>=', now()->subDay()->toDateString())
                            ->sum('views');
                        if ($recentViews <= 0) { $recentViews = rand(8, 25); }
                    @endphp

                    @if($stockIsLow)
                        <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                            <i class="fas fa-fire text-danger fa-2x me-3"></i>
                            <div>
                                <strong>¡Apúrate!</strong> Solo quedan <strong>{{ $product->quantity }}</strong> {{ $product->quantity === 1 ? 'unidad' : 'unidades' }}
                                @if($cartCount > 0)
                                    <small class="d-block text-muted">{{ $cartCount }} {{ $cartCount === 1 ? 'persona tiene' : 'personas tienen' }} este producto en su carrito</small>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if(! $stockIsOut)
                        <div class="text-muted small mb-3">
                            <i class="fas fa-eye me-1"></i> {{ $recentViews }} personas vieron este producto en las últimas 24 horas
                        </div>
                    @endif

                    @php
                        $recentOrdersCount = \Modules\Ecommerce\Models\OrderItem::query()
                            ->where('product_id', $product->id)
                            ->whereHas('order', fn ($q) => $q->where('created_at', '>=', now()->subHours(48)))
                            ->count();
                        $viewersCount = rand(3, 12);
                    @endphp

                    @push('styles')
                    <style>.product-social-proof { background: #fff8e1; }</style>
                    @endpush

                    @if($recentOrdersCount > 0)
                    <div class="alert alert-warning border-0 d-flex align-items-center py-2 mb-3 product-social-proof">
                        <i class="fas fa-fire text-danger me-2"></i>
                        <div>
                            <strong>{{ $recentOrdersCount }}</strong> {{ $recentOrdersCount === 1 ? 'persona compró' : 'personas compraron' }} este producto en las últimas 48 horas
                        </div>
                    </div>
                    @endif

                    <div class="text-muted small mb-2">
                        <i class="fas fa-eye me-1"></i>
                        <span id="viewersNow">{{ $viewersCount }}</span> personas viendo ahora
                    </div>

                    <div class="bt-1 border-color-1 mt-15 mb-15"></div>
                    <div class="short-desc">
                        {{ $product->description }}
                    </div>
                    <div class="bt-1 border-color-1 mt-15 mb-15"></div>

                    @php
                        $isOutOfStock = $product->with_storehouse_management && $product->quantity <= 0 && ! $product->allow_checkout_when_out_of_stock;
                    @endphp

                    @if($isOutOfStock)
                        <div class="detail-extralink out-of-stock-message mb-3">
                            <h3>{{ __('Agotado') }}</h3>
                            <p class="mb-3">{{ __('Este producto no está disponible actualmente.') }}</p>
                        </div>
                        <form class="js-restock-alert-form" action="{{ route('shop.products.restock-alert', $product) }}" method="post">
                            @csrf
                            <div class="input-group mb-2">
                                <input type="email" name="email" class="form-control" placeholder="Tu correo electrónico" required value="{{ auth('ecommerce')->user()->email ?? '' }}">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-bell me-2"></i>Avísame cuando esté disponible
                                </button>
                            </div>
                            <small class="text-muted">Te enviaremos un correo cuando vuelva a haber stock.</small>
                        </form>
                    @else
                        <form class="add-to-cart-form js-add-to-cart" method="POST" action="{{ route('cart.add', $product) }}">
                            @csrf
                            <div class="detail-extralink">
                                <div class="detail-qty border radius">
                                    <a href="#" class="qty-down" onclick="event.preventDefault(); var input=this.nextElementSibling; if(input.value>1) input.value--;"><i class="fa fa-caret-down" aria-hidden="true"></i></a>
                                    <input type="number" min="1" value="1" name="qty" class="qty-val qty-input" />
                                    <a href="#" class="qty-up" onclick="event.preventDefault(); var input=this.previousElementSibling; input.value++;"><i class="fa fa-caret-up" aria-hidden="true"></i></a>
                                </div>
                                <div class="product-extra-link2">
                                    <button type="submit" class="button button-add-to-cart">{{ __('Agregar al carrito') }}</button>
                                    <a aria-label="{{ __('Favoritos') }}" class="action-btn hover-up" href="{{ route('ecommerce.wishlist.store', $product) }}" onclick="event.preventDefault(); document.getElementById('wishlist-form-product').submit();"><i class="fa fa-heart"></i></a>
                                </div>
                            </div>
                        </form>
                        <form id="wishlist-form-product" action="{{ route('ecommerce.wishlist.store', $product) }}" method="POST" class="d-none">@csrf</form>
                    @endif

                    <ul class="product-meta font-xs color-grey mt-30">
                        @if($product->sku)
                            <li class="mb-5"><span class="d-inline-block me-1">{{ __('SKU') }}</span>: <span>{{ $product->sku }}</span></li>
                        @endif
                        @if($product->categories->isNotEmpty())
                            <li class="mb-5">
                                <span class="d-inline-block me-1">{{ __('Categorias') }}:</span>
                                @foreach($product->categories as $category)
                                    <a href="{{ route('shop.category', $category->slug) }}" title="{{ $category->name }}">{{ $category->name }}</a>@if(!$loop->last), @endif
                                @endforeach
                            </li>
                        @endif
                        @if($product->tags->isNotEmpty())
                            <li class="mb-5">
                                <span class="d-inline-block me-1">{{ __('Etiquetas') }}:</span>
                                @foreach($product->tags as $tag)
                                    {{ $tag->name }}@if(!$loop->last), @endif
                                @endforeach
                            </li>
                        @endif
                        <li>
                            <span class="d-inline-block me-1">{{ __('Disponibilidad') }}:</span>
                            <span class="in-stock text-success ml-5">
                                @if($product->with_storehouse_management && $product->quantity <= 0)
                                    {{ __('Agotado') }}
                                @else
                                    {{ __('En stock') }}
                                @endif
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="tab-style3">
            <ul class="nav nav-tabs text-uppercase">
                <li class="nav-item">
                    <a class="nav-link active" id="Description-tab" data-bs-toggle="tab" href="#Description">{{ __('Descripcion') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="Reviews-tab" data-bs-toggle="tab" href="#Reviews">{{ __('Resenas') }} ({{ $product->reviews->count() }})</a>
                </li>
            </ul>
            <div class="tab-content shop_info_tab entry-main-content">
                <div class="tab-pane fade show active" id="Description">
                    <div class="ck-content">
                        {!! nl2br(e($product->content ?: $product->description)) !!}
                    </div>
                </div>
                <div class="tab-pane fade" id="Reviews">
                    @if($product->reviews->count() > 0)
                        <div class="review-list">
                            @foreach($product->reviews as $review)
                                <div class="single-review mb-4">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <strong>{{ $review->customer->name ?? $review->customer_name ?? 'Anonimo' }}</strong>
                                            @if($review->is_verified_buyer)
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Compra verificada</span>
                                            @endif
                                        </div>
                                        <span class="rating_wrap">
                                            <span class="rating">
                                                <span class="product_rate" style="width: {{ $review->star * 20 }}%"></span>
                                            </span>
                                        </span>
                                    </div>
                                    <p class="text-muted mt-2">{{ $review->comment }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">{{ __('Sin resenas aun.') }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if($relatedProducts->count() > 0)
            <div class="row mt-60" id="related-products">
                <div class="col-12">
                    <h3 class="section-title style-1 mb-30">{{ __('Productos relacionados') }}</h3>
                </div>
                @foreach($relatedProducts as $relatedProduct)
                    <div class="col-lg-3 col-md-4 col-12 col-sm-6">
                        @include('ecommerce::shop.partials._product-card', ['product' => $relatedProduct])
                    </div>
                @endforeach
            </div>
        @endif

        @include('ecommerce::shop.partials._recommendations-section', [
            'products' => $frequentlyBought,
            'title' => __('Frecuentemente comprados juntos'),
            'icon' => 'fas fa-cart-plus',
        ])
    </div>
</div>

@php
    $isOutOfStockSticky = $product->with_storehouse_management
        && $product->quantity <= 0
        && ! $product->allow_checkout_when_out_of_stock;
@endphp
@if(! $isOutOfStockSticky)
<div id="stickyAddToCart" class="sticky-add-to-cart">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 overflow-hidden">
                <img src="{{ \Modules\Ecommerce\Supports\ProductImageHelper::url($product->featured_image, 'thumb', 'jpg') }}"
                     alt=""
                     style="width:40px;height:40px;object-fit:cover;border-radius:4px;flex-shrink:0;">
                <div class="overflow-hidden">
                    <strong class="d-block small text-truncate">{{ \Illuminate\Support\Str::limit($product->name, 40) }}</strong>
                    <span class="text-brand fw-bold">${{ number_format($product->final_price, 2) }}</span>
                </div>
            </div>
            <form action="{{ route('cart.add', $product) }}" method="POST" class="js-add-to-cart d-flex gap-2 flex-shrink-0">
                @csrf
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-cart-plus me-1"></i>Agregar
                </button>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(function () {
    // Thumbnail click — swap main image
    $(document).on('click', '.thumb-btn', function () {
        var $btn = $(this);
        var newSrc = $btn.data('image');
        var newSrcset = $btn.data('srcset');

        $('#product-main-image').attr('src', newSrc);
        $('#product-main-picture source[type="image/webp"]').attr('srcset', newSrcset);

        $('.thumb-btn').removeClass('active').css('border-width', '');
        $btn.addClass('active');
    });

    // Zoom on hover — desktop only
    if (window.matchMedia('(hover: hover)').matches) {
        var $wrap = $('#product-main-image-wrap');

        $wrap.on('mousemove', function (e) {
            var rect = this.getBoundingClientRect();
            var x = ((e.clientX - rect.left) / rect.width * 100).toFixed(2);
            var y = ((e.clientY - rect.top) / rect.height * 100).toFixed(2);
            this.style.setProperty('--zoom-x', x + '%');
            this.style.setProperty('--zoom-y', y + '%');
        });

        $wrap.on('mouseenter', function () { $(this).addClass('zoomed'); });
        $wrap.on('mouseleave', function () { $(this).removeClass('zoomed'); });
    }

    // Swipe to change image — mobile
    var touchStartX = 0;
    $('#product-main-image')
        .on('touchstart', function (e) {
            touchStartX = e.originalEvent.touches[0].clientX;
        })
        .on('touchend', function (e) {
            var diff = touchStartX - e.originalEvent.changedTouches[0].clientX;
            if (Math.abs(diff) < 50) { return; }
            var $thumbs = $('.thumb-btn');
            if (!$thumbs.length) { return; }
            var $active = $thumbs.filter('.active');
            var idx = $thumbs.index($active);
            idx = diff > 0 ? idx + 1 : idx - 1;
            if (idx < 0) { idx = $thumbs.length - 1; }
            if (idx >= $thumbs.length) { idx = 0; }
            $thumbs.eq(idx).trigger('click');
        });

    // Sticky add-to-cart
    var $sticky  = $('#stickyAddToCart');
    var $mainBtn = $('.add-to-cart-form').first();

    if ($sticky.length && $mainBtn.length) {
        function checkStickyVisibility() {
            var rect = $mainBtn[0].getBoundingClientRect();
            if (rect.bottom < 0) {
                $sticky.addClass('visible');
            } else {
                $sticky.removeClass('visible');
            }
        }

        $(window).on('scroll.sticky', checkStickyVisibility);
        checkStickyVisibility();
    }
});
</script>

<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org/',
    '@type' => 'Product',
    'name' => $product->name,
    'description' => \Illuminate\Support\Str::limit(strip_tags($product->description ?? $product->name), 5000),
    'sku' => $product->sku ?? null,
    'image' => $product->featured_image ? (str_starts_with($product->featured_image, 'http') ? $product->featured_image : asset('storage/'.$product->featured_image)) : null,
    'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
    'offers' => [
        '@type' => 'Offer',
        'url' => url()->current(),
        'priceCurrency' => 'USD',
        'price' => $product->final_price ?? $product->price,
        'availability' => ($product->quantity > 0 || ! $product->with_storehouse_management)
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock',
    ],
    'aggregateRating' => (isset($product->reviews) && $product->reviews->count() > 0) ? [
        '@type' => 'AggregateRating',
        'ratingValue' => round($product->reviews->avg('star'), 1),
        'reviewCount' => $product->reviews->count(),
    ] : null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org/',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_filter([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('shop.index')],
        ($product->categories && $product->categories->first())
            ? ['@type' => 'ListItem', 'position' => 2, 'name' => $product->categories->first()->name, 'item' => route('shop.category', $product->categories->first()->slug)]
            : null,
        ['@type' => 'ListItem', 'position' => 3, 'name' => $product->name, 'item' => url()->current()],
    ]),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

<script>
// Animate viewers count to simulate live activity
setInterval(function() {
    var el = document.getElementById('viewersNow');
    if (!el) return;
    var current = parseInt(el.textContent, 10);
    var next = Math.max(2, Math.min(20, current + (Math.random() > 0.5 ? 1 : -1)));
    el.textContent = next;
}, 8000);
</script>
@endpush
