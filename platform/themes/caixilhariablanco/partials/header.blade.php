<!DOCTYPE html>
<html {!! Theme::htmlAttributes() !!}>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('android-chrome-512x512.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#b10100">
    <meta name="msapplication-TileColor" content="#b10100">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Caixilharia Blanco">
    <meta name="format-detection" content="telephone=yes">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @hasSection('seo_head')
        @yield('seo_head')
    @else
        <title>@yield('title', theme_option('site_title', config('app.name')))</title>
        @hasSection('description')
            <meta name="description" content="@yield('description')">
        @endif
        @hasSection('keywords')
            <meta name="keywords" content="@yield('keywords')">
        @endif
    @endif

    @stack('canonical')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {!! BaseHelper::googleFonts('https://fonts.googleapis.com/css2?family=' . urlencode(theme_option('font_text', 'Poppins')) . ':ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap') !!}

    {!! Theme::asset()->container('default')->styles() !!}

    @stack('head')
    {!! Theme::place('head') !!}
    @schemaOrg
    @stack('schema_org')

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "Organization",
        "name": "{{ theme_option('site_title') ?: config('app.name') }}",
        "url": "{{ url('/') }}",
        "logo": "{{ theme_option('logo') ?: asset('images/logo.png') }}"
        @if(theme_option('phone')),"telephone": "{{ theme_option('phone') }}"@endif
        @if(theme_option('email')),"email": "{{ theme_option('email') }}"@endif
        @if(theme_option('address')),"address": {"@@type": "PostalAddress", "streetAddress": "{{ theme_option('address') }}"}@endif
    }
    </script>

    @php
        $headerStyle = theme_option('header_style') ?: '';
        $page = Theme::get('page');
    @endphp

    @seoWebVitalsBeacon
</head>
<body {!! Theme::bodyAttributes() !!} class="@if (BaseHelper::isRtlEnabled()) rtl @endif  @if (Theme::get('bodyClass')) {{ Theme::get('bodyClass') }} @endif">
{!! apply_filters(THEME_FRONT_BODY, null) !!}
<div id="alert-container"></div>

{!! Theme::partial('preloader') !!}


<div class="topbar">
    <div class="container">
        @php
            $activeLang   = $currentPageLocale ?? app()->getLocale();
            $hasLangLinks = collect($pageLangLinks ?? [])
                ->filter(fn($info, $lang) => $lang !== $activeLang && !empty($info['url']) && $info['published'])
                ->isNotEmpty();
        @endphp

        {{-- Mobile: columna centrada | Desktop: fila con espacio entre lados --}}
        <div class="d-flex flex-column flex-lg-row align-items-center justify-content-lg-between gap-1 gap-lg-3">

            {{-- Izquierda: dirección + email — visible siempre, centrado en mobile --}}
            <div class="topbar-contact-info">
                <ul class="justify-content-center justify-content-lg-start">
                    @if (theme_option('address'))
                        <li>
                            <a href="#">
                                <img src="{{ asset('themes/caixilhariablanco/images/icon-location.svg') }}" alt="Dirección" loading="lazy">
                                {{ theme_option('address') }}
                            </a>
                        </li>
                    @endif
                    @if (theme_option('email'))
                        <li>
                            <a href="mailto:{{ theme_option('email') }}">
                                <img src="{{ asset('themes/caixilhariablanco/images/icon-mail.svg') }}" alt="Correo electrónico" loading="lazy">
                                {{ theme_option('email') }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Derecha: teléfono/horario + idioma — centrado en mobile --}}
            <div class="d-flex align-items-center justify-content-center justify-content-lg-end gap-3">
                @if (theme_option('phone') || theme_option('opening_hours'))
                    <div class="topbar-time">
                        <ul>
                            <li>
                                <a href="{{ theme_option('phone') ? 'tel:' . theme_option('phone') : '#' }}">
                                    <img src="{{ asset('themes/caixilhariablanco/images/icon-clock.svg') }}" alt="Horario de atención" loading="lazy">
                                    @if (theme_option('opening_hours'))
                                        {{ theme_option('opening_hours') }}
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </div>
                @endif

                @if ($hasLangLinks)
                    <ul class="navbar-nav language-switcher-nav">
                        {!! Theme::partial('language-switcher') !!}
                    </ul>
                @endif
            </div>

        </div>
    </div>
</div>


<header class="main-header">
    <div class="header-sticky">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <!-- Logo Start -->
                @if (theme_option('logo'))
                    <a class="navbar-brand" href="{{ BaseHelper::getHomepageUrl() }}"><img src="{{ RvMedia::getImageUrl(theme_option('logo')) }}" alt="{{ theme_option('site_title') }}"></a>
                @endif
                <!-- Logo End -->

                <!-- Main Menu Start -->
                <div class="collapse navbar-collapse main-menu">

                            {!!
                                Menu::renderMenuLocation('main-menu', [
                                    'view' => 'main-menu',
                                ])
                            !!}

                            @if (($socialLinks = theme_option('social_links')) && $socialLinks = json_decode($socialLinks, true))
                                <div class="header-social-icons">
                                    <ul>
                                    @foreach($socialLinks as $socialLink)
                                        @if (!empty($socialLink['url']) && !empty($socialLink['icon']))
                                            <li><a href="{{ $socialLink['url'] }}"
                                                   title="{{ $socialLink['name'] ?? '' }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer">
                                                {!! BaseHelper::renderIcon($socialLink['icon']) !!}
                                            </a></li>
                                        @endif
                                    @endforeach
                                    </ul>
                                </div>
                            @endif



                </div>
                <!-- Main Menu End -->
                <div class="navbar-toggle"></div>
            </div>
        </nav>
        <div class="responsive-menu"></div>
    </div>
</header>
<!-- Header End -->

