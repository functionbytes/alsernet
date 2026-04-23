@php
    $seoPage      = $page ?? null;
    $seoMeta      = ($seoPage instanceof \Modules\Page\Models\Page) ? $seoPage->seoMeta : null;
    $seoLocale    = $detectedLocale ?? app()->getLocale();
    $seoLocaleMap = ['es' => 'es_ES', 'en' => 'en_US', 'fr' => 'fr_FR', 'pt' => 'pt_PT'];
    $seoOgLocale  = $seoLocaleMap[$seoLocale] ?? (strtolower($seoLocale).'_'.strtoupper($seoLocale));

    $seoTitle     = $seoMeta?->title ?: ($seoPage?->seo_title ?: ($transTitle ?? ($seoPage?->title ?? config('app.name'))));
    $seoDesc      = $seoMeta?->description ?: ($seoPage?->seo_description ?: ($transDescription ?? ($seoPage?->description ?? '')));

    // Fallback: extract hero image and description from rendered content
    $serviceShortcodeImage = null;
    $serviceShortcodeDesc = null;
    $renderedContent = ! empty($transContent) && function_exists('shortcode')
        ? shortcode($transContent)
        : ($transContent ?? '');
    if (! empty($renderedContent)) {
        // Extract hero background image from rendered HTML (e.g. background-image: url(...))
        if (preg_match('/background-image:\s*url\(["\']?([^"\')]+)["\']?\)/i', $renderedContent, $imgMatches)) {
            $serviceShortcodeImage = asset($imgMatches[1]);
        }
        // Extract first meaningful sentence for description
        $text = strip_tags($renderedContent);
        $text = preg_replace('/\s+/', ' ', $text);
        if (preg_match('/[a-záéíóúñ]{3,}[^.]{15,120}\./ui', $text, $descMatches)) {
            $serviceShortcodeDesc = \Illuminate\Support\Str::limit(trim($descMatches[0]), 160);
        }
    }

    if (empty($seoDesc) && ! empty($serviceShortcodeDesc)) {
        $seoDesc = $serviceShortcodeDesc;
    } elseif (empty($seoDesc) && ! empty($transContent)) {
        $seoDesc = \Illuminate\Support\Str::limit(strip_tags($transContent), 160);
    } elseif (empty($seoDesc) && ! empty($seoPage?->content)) {
        $seoDesc = \Illuminate\Support\Str::limit(strip_tags($seoPage->content), 160);
    }

    $defaultOgImage = config('Seo.default_og_image', 'https://caixilhariablanco.pt/media/seo/og-default.png');
    $isDefaultImage = empty($featuredImage) || $featuredImage === $defaultOgImage || str_contains($featuredImage, 'og-default');
    if ($isDefaultImage && ! empty($serviceShortcodeImage)) {
        $featuredImage = $serviceShortcodeImage;
    }
    $seoKeywords  = $seoMeta?->keywords ?: ($seoPage?->seo_keywords ?: ($transKeywords ?? null));
    $seoRobots    = $seoMeta?->robots ?: (($seoPage?->seo_noindex ?? false) ? 'noindex,nofollow' : 'index,follow');
    $seoCanonical = $seoMeta?->canonical_url ?: ($canonicalUrl ?? url()->current());

    $seoOgTitle   = $seoMeta?->og_title ?: $seoTitle;
    $seoOgDesc    = $seoMeta?->og_description ?: $seoDesc;
    $seoOgType    = $seoMeta?->og_type ?: 'website';
    $seoOgImage   = $seoMeta?->og_image ?: (($featuredImage ?? null) ?: config('Seo.default_og_image', 'https://caixilhariablanco.pt/media/seo/og-default.png'));
    if ($isDefaultImage && ! empty($serviceShortcodeImage)) {
        $seoOgImage = $serviceShortcodeImage;
    }

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
{{-- Don't preload featuredImage — OG images are for social sharing, not browser rendering.
     Preloading them wastes bandwidth and competes with LCP-critical resources.
@if(!empty($featuredImage))
    <link rel="preload" as="image" href="{{ $featuredImage }}">
@endif
--}}
