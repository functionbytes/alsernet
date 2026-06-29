@php
    $seo = $model->seoMeta ?? null;
    $title = $seo?->title ?? $model->title ?? config('app.name');
    $description = $seo?->description ?? $model->description ?? config('Seo.default_description');
    $appName = config('app.name');
    $currentUrl = url()->current();
@endphp

{{-- Basic SEO Meta Tags --}}
<title>{{ $title }}{{ config('Seo.default_title_suffix') }}</title>
<meta name="description" content="{{ $description }}">
@if($seo?->keywords)
<meta name="keywords" content="{{ $seo->keywords }}">
@endif

{{-- Robots --}}
<meta name="robots" content="{{ $seo?->robots ?? 'index,follow' }}">

{{-- Canonical URL --}}
@if($seo?->canonical_url)
<link rel="canonical" href="{{ $seo->canonical_url }}">
@elseif(config('Seo.canonical.enabled'))
<link rel="canonical" href="{{ $currentUrl }}">
@endif

{{-- Open Graph Meta Tags --}}
<meta property="og:title" content="{{ $seo?->og_title ?? $title }}">
<meta property="og:description" content="{{ $seo?->og_description ?? $description }}">
<meta property="og:url" content="{{ $currentUrl }}">
<meta property="og:type" content="{{ $seo?->og_type ?? 'website' }}">
<meta property="og:site_name" content="{{ config('Seo.og_defaults.site_name', $appName) }}">
<meta property="og:locale" content="{{ config('Seo.og_defaults.locale', 'en_US') }}">
@if($seo?->og_image)
<meta property="og:image" content="{{ $seo->og_image }}">
<meta property="og:image:width" content="{{ config('Seo.image_sizes.og.width', 1200) }}">
<meta property="og:image:height" content="{{ config('Seo.image_sizes.og.height', 630) }}">
@elseif(config('Seo.default_og_image'))
<meta property="og:image" content="{{ config('Seo.default_og_image') }}">
@endif

{{-- Twitter Card Meta Tags --}}
<meta name="twitter:card" content="{{ $seo?->twitter_card ?? config('Seo.twitter_defaults.card', 'summary') }}">
@if(config('Seo.twitter_site'))
<meta name="twitter:site" content="{{ config('Seo.twitter_site') }}">
@endif
<meta name="twitter:title" content="{{ $seo?->twitter_title ?? $title }}">
<meta name="twitter:description" content="{{ $seo?->twitter_description ?? $description }}">
@if($seo?->twitter_image)
<meta name="twitter:image" content="{{ $seo->twitter_image }}">
@elseif($seo?->og_image)
<meta name="twitter:image" content="{{ $seo->og_image }}">
@elseif(config('Seo.default_og_image'))
<meta name="twitter:image" content="{{ config('Seo.default_og_image') }}">
@endif

{{-- JSON-LD Schema.org Structured Data --}}
@if(config('Seo.schema.enabled', true))
    @php
        // Check if model has structured data support
        $hasStructuredData = method_exists($model ?? null, 'renderCompleteSchemaScript');

        if ($hasStructuredData && isset($model)) {
            // Use model's structured data
            echo $model->renderCompleteSchemaScript(config('app.debug', false));
        } else {
            // Use basic WebPage schema as fallback
            $schemaService = app(\Modules\Seo\Services\SchemaOrgService::class);
            $basicSchema = $schemaService->generateWebPageSchema([
                'title' => $title,
                'description' => $description,
                'url' => $currentUrl,
                'image' => $seo?->og_image ?? config('Seo.default_og_image'),
            ]);
            echo $schemaService->renderScriptTag($basicSchema, config('app.debug', false));
        }
    @endphp
@endif
