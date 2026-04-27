@extends('ecommerce::layouts.shop-wowy')

@section('title', __('Bundles y combos'))

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> {{ __('Bundles') }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">

        <div class="mb-4">
            <h2 class="fw-bold">{{ __('Bundles y combos') }}</h2>
            <p class="text-muted">{{ __('Ahorra más comprando productos combinados a precio especial.') }}</p>
        </div>

        @if($bundles->count() > 0)
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                @foreach($bundles as $bundle)
                    @php
                        $bundle->load('products');
                        $originalTotal = $bundle->calculateOriginalTotal();
                        $bundlePrice = $bundle->calculateBundlePrice();
                        $savings = $bundle->calculateSavings();
                    @endphp
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm">
                            @if($bundle->image)
                                <img src="{{ $bundle->image }}" alt="{{ $bundle->name }}"
                                    class="card-img-top" style="height: 200px; object-fit: cover;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center"
                                    style="height: 200px;">
                                    <i class="fas fa-box-open fa-3x text-muted opacity-50"></i>
                                </div>
                            @endif

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold">{{ $bundle->name }}</h5>

                                @if($bundle->description)
                                    <p class="card-text text-muted small flex-grow-1">{{ Str::limit($bundle->description, 100) }}</p>
                                @endif

                                <div class="mb-3">
                                    <span class="badge bg-success">{{ $bundle->products->count() }} productos</span>
                                    @if($savings > 0)
                                        <span class="badge bg-danger ms-1">
                                            Ahorra ${{ number_format($savings, 2) }}
                                        </span>
                                    @endif
                                </div>

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="fs-5 fw-bold text-success">${{ number_format($bundlePrice, 2) }}</span>
                                    @if($originalTotal > $bundlePrice)
                                        <small class="text-muted"><del>${{ number_format($originalTotal, 2) }}</del></small>
                                    @endif
                                </div>

                                <a href="{{ route('shop.bundles.show', $bundle->slug) }}"
                                    class="btn btn-primary w-100">
                                    {{ __('Ver bundle') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($bundles->hasPages())
                <div class="mt-5 d-flex justify-content-center">
                    {{ $bundles->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted opacity-50 mb-4"></i>
                <h4 class="text-muted">{{ __('No hay bundles disponibles por ahora.') }}</h4>
                <a href="{{ route('shop.index') }}" class="btn btn-primary mt-3">{{ __('Volver a la tienda') }}</a>
            </div>
        @endif

    </div>
</section>
@endsection
