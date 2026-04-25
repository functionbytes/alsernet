@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Tienda')

@section('content')
<section class="mt-60 mb-60">
    <div class="container mb-30">
        <div class="row">
            <div class="col-xl-3 col-lg-4 col-md-12">
                <div class="widget-area">
                    <div class="widget-category mb-30">
                        <h5 class="section-title style-1 mb-30">{{ __('Categorias') }}</h5>
                        <ul>
                            @foreach($categories as $category)
                                <li>
                                    <a href="{{ route('shop.category', $category->slug) }}">{{ $category->name }}</a>
                                    @if($category->children->count())
                                        <ul class="ml-20 mt-5">
                                            @foreach($category->children as $child)
                                                <li><a href="{{ route('shop.category', $child->slug) }}">{{ $child->name }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="widget-brand mb-30">
                        <h5 class="section-title style-1 mb-30">{{ __('Marcas destacadas') }}</h5>
                        <ul>
                            @foreach($brands as $brand)
                                <li><a href="{{ route('shop.brand', $brand->slug) }}">{{ $brand->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xl-9 col-lg-8 col-md-12">
                <div class="products-listing position-relative">
                    <div class="row">
                        @forelse($products as $product)
                            <div class="col-lg-4 col-md-4 col-12 col-sm-6">
                                <div class="product-cart-wrap mb-30">
                                    <div class="product-img-action-wrap">
                                        <div class="product-img product-img-zoom">
                                            <a href="{{ route('shop.product', $product->slug) }}">
                                                @if($product->featured_image)
                                                    <img class="default-img" src="{{ $product->featured_image }}" alt="{{ $product->name }}">
                                                @else
                                                    <img class="default-img" src="{{ asset('modules/ecommerce/images/404.png') }}" alt="{{ $product->name }}">
                                                @endif
                                            </a>
                                        </div>
                                        <div class="product-action-1">
                                            <a aria-label="{{ __('Ver') }}" href="{{ route('shop.product', $product->slug) }}" class="action-btn hover-up"><i class="fa fa-eye"></i></a>
                                            <a aria-label="{{ __('Favoritos') }}" href="{{ route('ecommerce.wishlist.store', $product) }}" class="action-btn hover-up" onclick="event.preventDefault(); document.getElementById('wishlist-form-{{ $product->id }}').submit();"><i class="fa fa-heart"></i></a>
                                            <form id="wishlist-form-{{ $product->id }}" action="{{ route('ecommerce.wishlist.store', $product) }}" method="POST" class="d-none">@csrf</form>
                                        </div>
                                        @if($product->is_on_sale)
                                            <div class="product-badges product-badges-position product-badges-mrg">
                                                <span class="hot">-{{ round((1 - $product->final_price / $product->price) * 100) }}%</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="product-content-wrap">
                                        <div class="product-category">
                                            @php $pcategory = $product->categories->first(); @endphp
                                            @if($pcategory)
                                                <a href="{{ route('shop.category', $pcategory->slug) }}">{{ $pcategory->name }}</a>
                                            @else
                                                &nbsp;
                                            @endif
                                        </div>
                                        <h2 class="text-truncate"><a href="{{ route('shop.product', $product->slug) }}" title="{{ $product->name }}">{{ $product->name }}</a></h2>
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
                                                <button type="submit" aria-label="{{ __('Agregar al carrito') }}" class="action-btn hover-up border-0 bg-transparent"><i class="fa fa-shopping-bag"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info text-center py-5">
                                    <i class="fas fa-box-open fa-3x mb-3"></i>
                                    <h4>{{ __('No hay productos disponibles.') }}</h4>
                                </div>
                            </div>
                        @endforelse
                    </div>
                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
