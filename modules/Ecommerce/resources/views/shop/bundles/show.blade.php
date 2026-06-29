@extends('ecommerce::layouts.shop-wowy')

@section('title', $bundle->name)

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> <a href="{{ route('shop.bundles.index') }}">{{ __('Bundles') }}</a>
            <span></span> {{ $bundle->name }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">

        @php
            $originalTotal = $bundle->calculateOriginalTotal();
            $bundlePrice = $bundle->calculateBundlePrice();
            $savings = $bundle->calculateSavings();
        @endphp

        <div class="row g-5">

            <div class="col-lg-5">
                @if($bundle->image)
                    <img src="{{ $bundle->image }}" alt="{{ $bundle->name }}"
                        class="img-fluid rounded shadow-sm w-100" style="max-height: 450px; object-fit: cover;">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center rounded"
                        style="height: 350px;">
                        <i class="fas fa-box-open fa-5x text-muted opacity-50"></i>
                    </div>
                @endif
            </div>

            <div class="col-lg-7">
                <h1 class="fw-bold mb-2">{{ $bundle->name }}</h1>

                @if($bundle->description)
                    <p class="text-muted mb-4">{{ $bundle->description }}</p>
                @endif

                {{-- Pricing --}}
                <div class="p-4 bg-light rounded mb-4">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="fs-2 fw-bold text-success">${{ number_format($bundlePrice, 2) }}</span>
                        @if($originalTotal > $bundlePrice)
                            <del class="fs-5 text-muted">${{ number_format($originalTotal, 2) }}</del>
                        @endif
                    </div>
                    @if($savings > 0)
                        <div class="alert alert-success py-2 mb-0">
                            <i class="fas fa-tag me-1"></i>
                            {{ __('Ahorras') }} <strong>${{ number_format($savings, 2) }}</strong>
                            @if($bundle->discount_type === 'percentage')
                                ({{ $bundle->discount_value }}% {{ __('de descuento') }})
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Add to cart --}}
                <form action="{{ route('shop.bundles.add-to-cart', $bundle) }}" method="POST" class="mb-4">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-cart-plus me-2"></i> {{ __('Agregar bundle al carrito') }}
                    </button>
                </form>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                {{-- Products in bundle --}}
                <div>
                    <h5 class="fw-bold mb-3">{{ __('Productos incluidos') }}</h5>
                    <div class="list-group">
                        @foreach($bundle->products as $product)
                            <div class="list-group-item list-group-item-action d-flex align-items-center gap-3 border-0 border-bottom py-3">
                                @if($product->featured_image)
                                    <img src="{{ $product->featured_image }}" alt="{{ $product->name }}"
                                        width="60" height="60" style="object-fit: cover;" class="rounded">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                        style="width:60px;height:60px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                @endif
                                <div class="flex-grow-1">
                                    <a href="{{ route('shop.product', $product->slug) }}"
                                        class="text-decoration-none fw-semibold text-dark">{{ $product->name }}</a>
                                    <div class="text-muted small">
                                        {{ __('Cantidad') }}: {{ $product->pivot->qty }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">${{ number_format(($product->sale_price ?? $product->price) * $product->pivot->qty, 2) }}</div>
                                    @if($product->sale_price)
                                        <small class="text-muted"><del>${{ number_format($product->price * $product->pivot->qty, 2) }}</del></small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
