<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <title>@yield('title', config('app.name', 'Laravel'))</title>

        {{-- SEO Meta Tags - Use Seo component if model has HasSeo trait --}}
        @if(isset($page) && method_exists($page, 'seoMeta'))
            <x-Seo::seo-tags :model="$page" />
        @else
            <meta name="description" content="@yield('meta_description', config('app.description', ''))">
            <meta name="keywords" content="@yield('meta_keywords', '')">
        @endif

        {{-- JSON-LD: custom schema per meta + auto-generated schemas via SeoService --}}
        @schemaOrg

        {{-- hreflang alternates for the current URL --}}
        @hreflang(url()->current())

        {{-- JSON-LD: schema.org para formularios embebidos via shortcode --}}
        @if(isset($page) && class_exists(\Modules\Forms\Services\FormJsonLdGenerator::class) && ! empty($page->content ?? ''))
            @php
                $formsLd = app(\Modules\Forms\Services\FormJsonLdGenerator::class)
                    ->generateForContent((string) $page->content, url()->current());
            @endphp
            @foreach($formsLd as $ld)
                <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
            @endforeach
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- GTM container script (head) --}}
        @seoGtmHead

        {{-- Additional Styles --}}
        @stack('styles')

        {{-- Vite CSS --}}
        {{-- {{ module_vite('build-page', 'resources/assets/sass/app.scss') }} --}}
    </head>

    <body>
        {{-- GTM noscript fallback — must be right after <body> --}}
        @seoGtmBody

        @yield('content')
        {{ $slot ?? '' }}

        {{-- Vite JS --}}
        {{-- {{ module_vite('build-page', 'resources/assets/js/app.js') }} --}}

        {{-- Additional Scripts --}}
        @stack('scripts')

        @if(\Modules\Core\Models\Setting::get('cookie.enabled') === '1')
            @include('cookie::index')
            <link rel="stylesheet" href="{{ url('modules/Cookie/css/cookie-consent.css') }}">
            <script src="{{ url('modules/Cookie/js/cookie-consent.js') }}"></script>
        @endif

        {{-- Core Web Vitals beacon (respects Seo.web_vitals.enabled + sample_rate) --}}
        @seoWebVitalsBeacon
    </body>
</html>
