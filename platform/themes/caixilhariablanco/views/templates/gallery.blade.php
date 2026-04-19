@extends('template::layouts.default')

@php
    Theme::set('page', $page);
    $locale      = $detectedLocale ?? app()->getLocale();
    $ogLocaleMap = ['es' => 'es_ES', 'en' => 'en_US', 'fr' => 'fr_FR', 'pt' => 'pt_PT'];
    $ogLocale    = $ogLocaleMap[$locale] ?? (strtolower($locale).'_'.strtoupper($locale));
    $twitterCard = $featuredImage ? 'summary_large_image' : 'summary';

    $galleryImages = collect();
    if (isset($page) && $page instanceof \Modules\Page\Models\Page) {
        try {
            $galleryImages = $page->getMedia('gallery') ?? collect();
        } catch (\Exception $e) {
            // Media library not available or no gallery collection
        }
    }
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

    <section class="mt-60 mb-5">
        <div class="container">
            {{-- Contenido CKEditor --}}
            @if($transContent)
            <div class="ck-content mb-4">
                {!! $transContent !!}
            </div>
            @endif

            {{-- Galería lightbox --}}
            @if($galleryImages->isNotEmpty())
            <div class="row g-3">
                @foreach($galleryImages as $media)
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ $media->getUrl() }}" class="popup-image d-block overflow-hidden rounded"
                       style="height:200px;">
                        <img alt="" src="{{ $media->getUrl('thumbnail') ?: $media->getUrl() }}"
                             alt="{{ $media->name }}"
                             class="w-100 h-100 object-fit-cover"
                             loading="lazy">
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </section>
@endsection
