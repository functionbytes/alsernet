@php
    $form->loadMissing('seoMeta');
    $publicUrl = url()->current();
    $seoTitle = $form->seo_title ?? $form->name;
    $seoDescription = $form->seo_description ?? $form->description ?? config('forms.default_success_message');
    $ogImage = $form->og_image ?? null;
    $robots = $form->robots ?? 'index,follow';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $form->canonical_url ?? $publicUrl }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $form->og_type ?? 'website' }}">
    <meta property="og:title" content="{{ $form->og_title ?? $seoTitle }}">
    <meta property="og:description" content="{{ $form->og_description ?? $seoDescription }}">
    <meta property="og:url" content="{{ $publicUrl }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    {{-- Twitter --}}
    <meta name="twitter:card" content="{{ $form->twitter_card ?? 'summary' }}">
    <meta name="twitter:title" content="{{ $form->twitter_title ?? $seoTitle }}">
    <meta name="twitter:description" content="{{ $form->twitter_description ?? $seoDescription }}">
    @if($form->twitter_image ?? $ogImage)
        <meta name="twitter:image" content="{{ $form->twitter_image ?? $ogImage }}">
    @endif

    {{-- JSON-LD: WebPage + CommunicateAction --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $seoTitle,
        'description' => $seoDescription,
        'url' => $publicUrl,
        'inLanguage' => app()->getLocale(),
        'potentialAction' => [
            '@type' => 'CommunicateAction',
            'name' => $form->submit_button_text ?? 'Enviar formulario',
            'target' => route('forms.public.submit', $form->slug),
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <link rel="stylesheet" href="{{ asset('core/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/forms/css/forms.css') }}">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    {{-- Se delega en public.render en vez de incluir form-body a pelo:
                         render es quien carga jQuery Validate y Select2 antes de
                         forms.min.js. Incluyendo form-body directamente, forms.min.js
                         se ejecutaba sin Validate y `$form.validate(...)` reventaba,
                         abortando initForm() -- el botón "Siguiente" de un formulario
                         multi-paso se quedaba sin handler y la página era inservible,
                         sin un solo error en consola. Mismo patrón que public.embed. --}}
                    @include('forms::public.render', [
                        'form' => $form,
                        'shortcodeConfig' => [
                            'display' => 'inline',
                            'show_title' => true,
                            'button_text' => $form->submit_button_text ?? 'Enviar',
                            'theme' => $form->theme ?? 'default',
                        ],
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('core/js/jquery-3.6.4.min.js') }}"></script>
<script src="{{ asset('core/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
