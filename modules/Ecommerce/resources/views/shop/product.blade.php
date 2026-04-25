@extends('ecommerce::layouts.shop-wowy')

@section('title', $product->name)

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
                <div class="detail-gallery">
                    <div class="product-image-slider">
                        <figure class="border-radius-10">
                            @if($product->featured_image)
                                <img src="{{ $product->featured_image }}" alt="{{ $product->name }}" class="img-fluid">
                            @else
                                <img src="{{ asset('modules/ecommerce/images/404.png') }}" alt="{{ $product->name }}" class="img-fluid">
                            @endif
                        </figure>
                    </div>
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
                    <div class="bt-1 border-color-1 mt-15 mb-15"></div>
                    <div class="short-desc">
                        {{ $product->description }}
                    </div>
                    <div class="bt-1 border-color-1 mt-15 mb-15"></div>

                    @if($product->with_storehouse_management && $product->quantity <= 0 && !$product->allow_checkout_when_out_of_stock)
                        <div class="detail-extralink out-of-stock-message">
                            <h3>{{ __('Agotado') }}</h3>
                            <p>{{ __('Este producto no esta disponible actualmente.') }}</p>
                        </div>
                    @else
                        <form class="add-to-cart-form" method="POST" action="{{ route('cart.add', $product) }}">
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
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $review->customer->name ?? $review->customer_name ?? 'Anonimo' }}</strong>
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
                        <div class="product-cart-wrap mb-30">
                            <div class="product-img-action-wrap">
                                <div class="product-img product-img-zoom">
                                    <a href="{{ route('shop.product', $relatedProduct->slug) }}">
                                        @if($relatedProduct->featured_image)
                                            <img class="default-img" src="{{ $relatedProduct->featured_image }}" alt="{{ $relatedProduct->name }}">
                                        @else
                                            <img class="default-img" src="{{ asset('modules/ecommerce/images/404.png') }}" alt="{{ $relatedProduct->name }}">
                                        @endif
                                    </a>
                                </div>
                                <div class="product-action-1">
                                    <a aria-label="{{ __('Ver') }}" href="{{ route('shop.product', $relatedProduct->slug) }}" class="action-btn hover-up"><i class="fa fa-eye"></i></a>
                                </div>
                                @if($relatedProduct->is_on_sale)
                                    <div class="product-badges product-badges-position product-badges-mrg">
                                        <span class="hot">-{{ round((1 - $relatedProduct->final_price / $relatedProduct->price) * 100) }}%</span>
                                    </div>
                                @endif
                            </div>
                            <div class="product-content-wrap">
                                <h2 class="text-truncate"><a href="{{ route('shop.product', $relatedProduct->slug) }}">{{ $relatedProduct->name }}</a></h2>
                                <div class="product-price">
                                    <span>${{ number_format($relatedProduct->final_price, 2) }}</span>
                                    @if($relatedProduct->is_on_sale)
                                        <span class="old-price">${{ number_format($relatedProduct->price, 2) }}</span>
                                    @endif
                                </div>
                                <div class="product-action-1 show" style="bottom: 10px;">
                                    <form action="{{ route('cart.add', $relatedProduct) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit" aria-label="{{ __('Agregar al carrito') }}" class="action-btn hover-up border-0 bg-transparent"><i class="fa fa-shopping-bag"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
