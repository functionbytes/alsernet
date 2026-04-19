@extends('template::layouts.default')

@php
    Theme::set('page', $page);
    $template     = $page->template ?? 'default';
    $locale       = $detectedLocale ?? app()->getLocale();
    $ogLocaleMap  = ['es' => 'es_ES', 'en' => 'en_US', 'fr' => 'fr_FR', 'pt' => 'pt_PT'];
    $ogLocale     = $ogLocaleMap[$locale] ?? (strtolower($locale).'_'.strtoupper($locale));
    $ogImage      = $featuredImage
        ?: (theme_option('logo') ?: null);
    $twitterCard  = $ogImage ? 'summary_large_image' : 'summary';
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

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $transTitle ?? config('app.name') }}">
    <meta property="og:description" content="{{ $transDescription ?? '' }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="{{ theme_option('site_title') ?: config('app.name') }}">
    <meta property="og:locale" content="{{ $ogLocale }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:alt" content="{{ $transTitle ?? config('app.name') }}">
    @endif
    @if(!empty($langLinks))
        @foreach($langLinks as $lang => $info)
            @if($lang !== $locale && !empty($info['url']) && $info['published'])
                <meta property="og:locale:alternate" content="{{ $ogLocaleMap[$lang] ?? (strtolower($lang).'_'.strtoupper($lang)) }}">
            @endif
        @endforeach
    @endif

    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $transTitle ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $transDescription ?? '' }}">
    @if($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
    @if(theme_option('twitter_handle'))
        <meta name="twitter:site" content="@{{ ltrim(theme_option('twitter_handle'), '@') }}">
    @endif
    @if($featuredImage)
        <link rel="preload" as="image" href="{{ $featuredImage }}">
    @endif
@endsection

@section('content')

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

    @if($template === 'default')
        <section class="mt-60 mb-60">
            <div class="ck-content">
                @if($transContent)
                    {!! $transContent !!}
                @else
                    <p class="text-muted text-center">{{ trans('page::messages.no_content') }}</p>
                @endif
            </div>
            @if(config('page.social_share.enabled', true) && view()->exists('template::partials.social-share'))
                @include('template::partials.social-share')
            @endif
        </section>
    @else
        <div class="ck-content">
            @if($transContent)
                {!! $transContent !!}
            @else
                <p class="text-muted text-center">{{ trans('page::messages.no_content') }}</p>
            @endif
        </div>
        @if(config('page.social_share.enabled', true) && view()->exists('template::partials.social-share'))
            @include('template::partials.social-share')
        @endif
    @endif
@endsection

{{-- TOC automática para páginas con múltiples headings --}}
@push('scripts')
<script>
// Time-on-page tracking
(function () {
    var pageId = {{ $page->id ?? 'null' }};
    var ipHash = '{{ hash("sha256", request()->ip()) }}';
    var trackUrl = '{{ url("/api/v1/pages/track-time") }}';

    if (!pageId) { return; }

    var startTime = Date.now();

    function sendTime(isUnload) {
        var seconds = Math.round((Date.now() - startTime) / 1000);
        if (seconds < 1) { return; }

        var data = JSON.stringify({ page_id: pageId, time_seconds: seconds, ip_hash: ipHash });
        var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

        if (isUnload && navigator.sendBeacon) {
            navigator.sendBeacon(trackUrl, new Blob([data], { type: 'application/json' }));
            return;
        }

        fetch(trackUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: data,
            keepalive: true,
        }).catch(function () {});
    }

    window.addEventListener('beforeunload', function () { sendTime(true); });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') { sendTime(false); }
    });
}());
</script>
@endpush
