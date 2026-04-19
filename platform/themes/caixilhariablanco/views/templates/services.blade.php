@extends('template::layouts.default')

@php
    Theme::set('page', $page);
    $locale      = $detectedLocale ?? app()->getLocale();
    $ogLocaleMap = ['es' => 'es_ES', 'en' => 'en_US', 'fr' => 'fr_FR', 'pt' => 'pt_PT'];
    $ogLocale    = $ogLocaleMap[$locale] ?? (strtolower($locale).'_'.strtoupper($locale));
    $twitterCard = $featuredImage ? 'summary_large_image' : 'summary';
@endphp

@section('title', $transTitle ?? config('app.name'))
@section('description', $transDescription ?? '')
@if(!empty($transKeywords))
    @section('keywords', $transKeywords)
@endif

@section('seo_head')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if(!empty($langLinks))
        @foreach($langLinks as $lang => $info)
            @if(!empty($info['url']) && $info['published'])
                <link rel="alternate" hreflang="{{ $lang }}" href="{{ $info['url'] }}">
            @endif
        @endforeach
        @if(!empty($xDefaultUrl))
            <link rel="alternate" hreflang="x-default" href="{{ $xDefaultUrl }}">
        @endif
    @endif
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $transTitle ?? config('app.name') }}">
    <meta property="og:description" content="{{ $transDescription ?? '' }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if($featuredImage)
        <meta property="og:image" content="{{ $featuredImage }}">
        <link rel="preload" as="image" href="{{ $featuredImage }}">
    @endif
    <meta property="og:locale" content="{{ $ogLocale }}">
    <meta property="og:site_name" content="{{ theme_option('site_title', config('app.name')) }}">
    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $transTitle ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $transDescription ?? '' }}">
    @if($featuredImage)
        <meta name="twitter:image" content="{{ $featuredImage }}">
    @endif
@endsection

@section('content')
    {{-- Breadcrumb --}}
    @if($transTitle ?? null)
    <div class="breadcrumb-area py-3 bg-light border-bottom">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item">
                        <a href="{{ url('/') }}" class="text-decoration-none">
                            <i class="fas fa-home me-1"></i>Inicio
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $transTitle }}</li>
                </ol>
            </nav>
        </div>
    </div>
    @endif

    {{-- Hero con imagen destacada --}}
    @if($featuredImage)
    <div class="page-hero position-relative overflow-hidden"
         style="height:280px;background:url('{{ $featuredImage }}') center/cover no-repeat;">
        <div class="position-absolute top-0 start-0 w-100 h-100"
             style="background:rgba(0,0,0,0.45);"></div>
        <div class="container h-100 position-relative d-flex align-items-center">
            <h1 class="text-white fw-bold mb-0" style="text-shadow:0 2px 8px rgba(0,0,0,.5);">
                {{ $transTitle ?? '' }}
            </h1>
        </div>
    </div>
    @endif

    {{-- Contenido CKEditor --}}
    @if($transContent)
    <section class="mt-60 mb-5">
        <div class="container">
            <div class="ck-content">
                {!! $transContent !!}
            </div>
        </div>
    </section>
    @endif

    {{-- Grid de servicios --}}
    <section class="our-services pb-5">
        <div class="container">
            <div class="row g-4">
                @foreach([
                    ['icon' => 'fas fa-window-maximize', 'title' => 'Ventanas PVC',         'desc' => 'Alta eficiencia energética y aislamiento acústico'],
                    ['icon' => 'fas fa-door-open',       'title' => 'Portones automáticos',  'desc' => 'Sistemas de automatización residencial e industrial'],
                    ['icon' => 'fas fa-layer-group',     'title' => 'Estores y persianas',   'desc' => 'Control de luz y privacidad con diseño elegante'],
                    ['icon' => 'fas fa-tools',           'title' => 'Instalación',           'desc' => 'Equipo certificado con garantía de instalación'],
                ] as $service)
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="mb-3">
                            <i class="{{ $service['icon'] }} fa-3x" style="color:var(--main-color, #90bb13);"></i>
                        </div>
                        <h5 class="fw-bold">{{ $service['title'] }}</h5>
                        <p class="text-muted small mb-0">{{ $service['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
