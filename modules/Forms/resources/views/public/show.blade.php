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

    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/forms/css/forms.css') }}">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    @include('forms::public.partials.form-body', [
                        'formId'         => 'form-' . $form->id,
                        'form'           => $form,
                        'isMultiStep'    => $form->is_multi_step,
                        'steps'          => $form->fields->pluck('step_number')->unique()->sort()->values(),
                        'totalSteps'     => $form->fields->pluck('step_number')->unique()->count(),
                        'floatingLabel'  => $form->floating_label ?? false,
                        'buttonText'     => $form->submit_button_text ?? 'Enviar',
                        'buttonColor'    => $form->button_color ?? null,
                        'showTitle'      => true,
                        'theme'          => $form->theme ?? 'default',
                        'captchaEnabled' => $form->captcha_enabled && class_exists(\Modules\Captcha\Facades\Captcha::class),
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('modules/forms/js/forms.js') }}"></script>
</body>
</html>
