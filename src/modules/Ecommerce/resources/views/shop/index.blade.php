@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Tienda')

@section('content')
<section class="hero-banner mb-5">
    <div class="container-fluid p-0">
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="hero-slide" style="background:linear-gradient(135deg,#90bb13 0%,#7da210 100%)">
                        <div class="container">
                            <div class="row align-items-center min-vh-50 py-5">
                                <div class="col-md-7 text-white">
                                    <span class="badge bg-light text-dark mb-3">{{ __('Nueva colección') }}</span>
                                    <h1 class="display-4 fw-bold mb-3">{{ __('Compra inteligente, vive mejor') }}</h1>
                                    <p class="lead mb-4">{{ __('Descubre productos seleccionados con la mejor calidad y los mejores precios.') }}</p>
                                    <a href="#productos-destacados" class="btn btn-light btn-lg">{{ __('Ver catálogo') }} <i class="fas fa-arrow-right ms-2"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="hero-slide" style="background:linear-gradient(135deg,#1a2030 0%,#3a4256 100%)">
                        <div class="container">
                            <div class="row align-items-center min-vh-50 py-5">
                                <div class="col-md-7 text-white">
                                    <span class="badge bg-warning text-dark mb-3">{{ __('Ofertas limitadas') }}</span>
                                    <h1 class="display-4 fw-bold mb-3">{{ __('Hasta 50% OFF') }}</h1>
                                    <p class="lead mb-4">{{ __('Aprovecha nuestras ofertas relámpago. Stock limitado.') }}</p>
                                    <a href="{{ route('shop.index', ['filter' => 'on_sale']) }}" class="btn btn-warning btn-lg">{{ __('Ver ofertas') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="hero-slide" style="background:linear-gradient(135deg,#13C672 0%,#0f9d5b 100%)">
                        <div class="container">
                            <div class="row align-items-center min-vh-50 py-5">
                                <div class="col-md-7 text-white">
                                    <span class="badge bg-light text-dark mb-3">{{ __('Envío gratis') }}</span>
                                    <h1 class="display-4 fw-bold mb-3">{{ __('Recíbelo en tu puerta') }}</h1>
                                    <p class="lead mb-4">{{ __('Envío gratis en compras mayores a $200. Sin mínimos en zona metropolitana.') }}</p>
                                    <a href="{{ route('shop.legal.show', 'shipping-policy') }}" class="btn btn-light btn-lg">{{ __('Ver política') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
        </div>
    </div>
</section>

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
                            <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                                @include('ecommerce::shop.partials._product-card', ['product' => $product])
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

<section class="mt-0 mb-60">
    <div class="container">
        @include('ecommerce::shop.partials._recommendations-section', [
            'products' => $suggestedProducts ?? collect(),
            'title' => auth('ecommerce')->check() ? __('Recomendado para ti') : __('Productos populares'),
            'icon' => 'fas fa-magic',
        ])
    </div>
</section>
@endsection
