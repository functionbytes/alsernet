@php
    $pageTitle = $pageTitle ?? 'Productos';
@endphp

@extends('ecommerce::layouts.shop-wowy')

@section('title', $pageTitle)

@section('meta')
@php
    $og            = $ogData ?? [];
    $ogType        = $og['type']        ?? 'website';
    $ogTitle       = $og['title']       ?? $pageTitle;
    $ogDescription = $og['description'] ?? $pageTitle;
    $ogImage       = $og['image']       ?? asset('modules/ecommerce/images/404.png');
    $ogUrl         = $og['url']         ?? url()->current();
@endphp
<link rel="canonical" href="{{ $ogUrl }}">
<meta name="description" content="{{ $ogDescription }}">
{{-- Open Graph --}}
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
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
            <span></span> {{ $pageTitle }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container mb-30">
        <button type="button" class="mobile-filters-toggle mb-3" id="openMobileFilters" aria-label="Abrir filtros">
            <i class="fas fa-filter"></i> Filtros
        </button>
        <div class="filter-overlay" id="filterOverlay"></div>

        <div class="row g-4">
            <div class="col-lg-3">
                @include('ecommerce::shop.partials._product-filters')
            </div>

            <div class="col-lg-9">
                <div id="productsContainer">
                    <div class="row">
                        @forelse($products as $product)
                            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                                @include('ecommerce::shop.partials._product-card', ['product' => $product])
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info text-center py-5">
                                    <i class="fas fa-box-open fa-3x mb-3"></i>
                                    <h4>{{ __('No se encontraron productos.') }}</h4>
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
