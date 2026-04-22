@php
    $seoPage      = $page ?? null;
    $seoMeta      = ($seoPage instanceof \Modules\Page\Models\Page) ? $seoPage->seoMeta : null;
    $seoLocale    = $detectedLocale ?? app()->getLocale();
    $seoLocaleMap = ['es' => 'es_ES', 'en' => 'en_US', 'fr' => 'fr_FR', 'pt' => 'pt_PT'];
    $seoOgLocale  = $seoLocaleMap[$seoLocale] ?? (strtolower($seoLocale).'_'.strtoupper($seoLocale));

    $seoTitle     = $seoMeta?->title ?: ($seoPage?->seo_title ?: ($transTitle ?? ($seoPage?->title ?? config('app.name'))));
    $seoDesc      = $seoMeta?->description ?: ($seoPage?->seo_description ?: ($transDescription ?? ($seoPage?->description ?? '')));
    $seoKeywords  = $seoMeta?->keywords ?: ($seoPage?->seo_keywords ?: ($transKeywords ?? null));
    $seoRobots    = $seoMeta?->robots ?: (($seoPage?->seo_noindex ?? false) ? 'noindex,nofollow' : 'index,follow');
    $seoCanonical = $seoMeta?->canonical_url ?: ($canonicalUrl ?? url()->current());

    $seoOgTitle   = $seoMeta?->og_title ?: $seoTitle;
    $seoOgDesc    = $seoMeta?->og_description ?: $seoDesc;
    $seoOgType    = $seoMeta?->og_type ?: 'website';
    $seoOgImage   = $seoMeta?->og_image ?: (($featuredImage ?? null) ?: config('Seo.default_og_image', 'https://caixilhariablanco.pt/media/seo/og-default.png'));

    $seoTwCard    = $seoMeta?->twitter_card ?: ($seoOgImage ? 'summary_large_image' : 'summary');
    $seoTwTitle   = $seoMeta?->twitter_title ?: $seoOgTitle;
    $seoTwDesc    = $seoMeta?->twitter_description ?: $seoOgDesc;
    $seoTwImage   = $seoMeta?->twitter_image ?: $seoOgImage;
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDesc }}">
@if(!empty($seoKeywords))
    <meta name="keywords" content="{{ $seoKeywords }}">
@endif
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $seoCanonical }}">

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

<meta property="og:type" content="{{ $seoOgType }}">
<meta property="og:title" content="{{ $seoOgTitle }}">
<meta property="og:description" content="{{ $seoOgDesc }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:site_name" content="{{ theme_option('site_title') ?: config('app.name') }}">
<meta property="og:locale" content="{{ $seoOgLocale }}">
@if($seoOgImage)
    <meta property="og:image" content="{{ $seoOgImage }}">
    <meta property="og:image:alt" content="{{ $seoOgTitle }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
@endif
@if(!empty($langLinks))
    @foreach($langLinks as $lang => $info)
        @if($lang !== $seoLocale && !empty($info['url']) && $info['published'])
            <meta property="og:locale:alternate" content="{{ $seoLocaleMap[$lang] ?? (strtolower($lang).'_'.strtoupper($lang)) }}">
        @endif
    @endforeach
@endif

<meta name="twitter:card" content="{{ $seoTwCard }}">
<meta name="twitter:title" content="{{ $seoTwTitle }}">
<meta name="twitter:description" content="{{ $seoTwDesc }}">
@if($seoTwImage)
    <meta name="twitter:image" content="{{ $seoTwImage }}">
@endif
@if(theme_option('twitter_handle'))
    <meta name="twitter:site" content="{{ '@'.ltrim(theme_option('twitter_handle'), '@') }}">
@endif
@if(!empty($featuredImage))
    <link rel="preload" as="image" href="{{ $featuredImage }}">
@endif
