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

    <section class="mt-60 mb-5">
        <div class="container">
            {{-- Contenido CKEditor --}}
            @if($transContent)
            <div class="ck-content mb-4">
                {!! $transContent !!}
            </div>
            @endif

            <div class="row g-5">
                {{-- Datos de contacto --}}
                <div class="col-lg-4">
                    <h3 class="mb-4">Información de contacto</h3>

                    @if(theme_option('address'))
                    <div class="d-flex mb-3">
                        <i class="fas fa-map-marker-alt fa-lg mt-1 me-3" style="color:#90bb13;"></i>
                        <div>
                            <div class="fw-semibold">Dirección</div>
                            <div class="text-muted small">{{ theme_option('address') }}</div>
                        </div>
                    </div>
                    @endif

                    @if(theme_option('phone'))
                    <div class="d-flex mb-3">
                        <i class="fas fa-phone fa-lg mt-1 me-3" style="color:#90bb13;"></i>
                        <div>
                            <div class="fw-semibold">Teléfono</div>
                            <a href="tel:{{ preg_replace('/\s/', '', theme_option('phone')) }}"
                               class="text-muted small text-decoration-none">
                                {{ theme_option('phone') }}
                            </a>
                        </div>
                    </div>
                    @endif

                    @if(theme_option('contact_email'))
                    <div class="d-flex mb-3">
                        <i class="fas fa-envelope fa-lg mt-1 me-3" style="color:#90bb13;"></i>
                        <div>
                            <div class="fw-semibold">Email</div>
                            <a href="mailto:{{ theme_option('contact_email') }}"
                               class="text-muted small text-decoration-none">
                                {{ theme_option('contact_email') }}
                            </a>
                        </div>
                    </div>
                    @endif

                    @if(theme_option('working_hours'))
                    <div class="d-flex mb-3">
                        <i class="fas fa-clock fa-lg mt-1 me-3" style="color:#90bb13;"></i>
                        <div>
                            <div class="fw-semibold">Horario</div>
                            <div class="text-muted small">{{ theme_option('working_hours') }}</div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Formulario de contacto --}}
                <div class="col-lg-8">
                    @include(Theme::getThemeNamespace() . '::partials.shortcodes.contact-form')
                </div>
            </div>
        </div>
    </section>
@endsection
