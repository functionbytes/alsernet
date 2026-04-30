<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Hreflang --}}
    @foreach(config('app.locales', ['es' => 'Español']) as $code => $name)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ url()->current() }}{{ str_contains(url()->current(), '?') ? '&' : '?' }}lang={{ $code }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#b10100">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Tienda') }}">

    <title>@yield('title', 'Tienda') - {{ config('app.name', 'Alsernet') }}</title>

    @include('ecommerce::shop.partials._analytics-tags')

    <link rel="stylesheet" href="{{ asset('modules/ecommerce/plugins/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/ecommerce/plugins/fontawesome/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/ecommerce/css/style.css') }}">

    <style>
        :root {
            --font-text: 'Poppins', sans-serif;
            --color-brand: #b10100;
            --primary-color: #b10100;
            --color-brand-2: #7da210;
            --color-primary: #b10100;
            --color-secondary: #41506b;
            --color-warning: #ffb300;
            --color-danger: #ff3551;
            --color-success: #3ed092;
            --color-info: #18a1b7;
            --color-text: #4f5d77;
            --color-heading: #222222;
            --color-grey-1: #111111;
            --color-grey-2: #242424;
            --color-grey-4: #90908e;
            --color-grey-9: #f8f9fa;
            --color-muted: #8e8e90;
            --color-body: #4f5d77;
        }

        /* Product skeleton loader */
        .product-skeleton {
            background: #f4f4f4;
            border-radius: 8px;
            overflow: hidden;
            animation: skeleton-pulse 1.4s ease-in-out infinite;
        }
        .product-skeleton .skeleton-img {
            width: 100%;
            aspect-ratio: 1;
            background: linear-gradient(90deg, #eee 25%, #f5f5f5 50%, #eee 75%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.5s infinite;
        }
        .product-skeleton .skeleton-line {
            height: 12px;
            margin: 8px 12px;
            background: #eee;
            border-radius: 4px;
        }
        .product-skeleton .skeleton-line.short { width: 60%; }
        @keyframes skeleton-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        @keyframes skeleton-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Button loading spinner */
        .btn-loading { position: relative; pointer-events: none; opacity: 0.7; }
        .btn-loading::after {
            content: ''; position: absolute; top: 50%; right: 12px;
            width: 14px; height: 14px;
            margin-top: -7px;
            border: 2px solid currentColor; border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Inline field validation */
        .input-invalid { border-color: #dc3545 !important; }
        .input-valid { border-color: #198754 !important; }
        .field-error { color: #dc3545; font-size: 0.8125rem; margin-top: 4px; }

        /* Product gallery */
        .main-image-wrap {
            overflow: hidden;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .product-zoom-img {
            display: block;
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            cursor: zoom-in;
            transition: transform 0.3s ease;
        }
        .main-image-wrap.zoomed .product-zoom-img {
            transform: scale(2);
            transform-origin: var(--zoom-x, 50%) var(--zoom-y, 50%);
        }
        .product-thumbnails {
            flex-wrap: wrap;
        }
        .thumb-btn {
            width: 70px;
            height: 70px;
            flex: 0 0 auto;
            border-radius: 4px;
            border-color: #dee2e6 !important;
            overflow: hidden;
            background: none;
            transition: border-color 0.2s;
        }
        .thumb-btn img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .thumb-btn:hover { border-color: #b10100 !important; }
        .thumb-btn.active { border-color: #b10100 !important; border-width: 2px !important; }
        @media (max-width: 767px) {
            .product-thumbnails {
                flex-wrap: nowrap !important;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 4px;
            }
            .thumb-btn { scroll-snap-align: start; }
        }

        /* WhatsApp floating button */
        .whatsapp-floating-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            background: #25D366;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 16px rgba(37, 211, 102, 0.4);
            text-decoration: none;
            z-index: 9999;
            transition: transform 0.2s, box-shadow 0.2s;
            animation: wa-pulse 2s ease-in-out infinite;
        }
        .whatsapp-floating-btn:hover {
            color: #fff;
            transform: scale(1.1);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        }
        @keyframes wa-pulse {
            0%, 100% { box-shadow: 0 4px 16px rgba(37, 211, 102, 0.4); }
            50% { box-shadow: 0 4px 16px rgba(37, 211, 102, 0.8), 0 0 0 12px rgba(37, 211, 102, 0); }
        }
        @media (max-width: 575px) {
            .whatsapp-floating-btn {
                bottom: 16px;
                right: 16px;
                width: 48px;
                height: 48px;
                font-size: 24px;
            }
        }

        /* Cart table: stack as cards on mobile */
        @media (max-width: 767px) {
            .cart-table-responsive thead { display: none; }
            .cart-table-responsive tbody,
            .cart-table-responsive tr,
            .cart-table-responsive td { display: block; width: 100%; }
            .cart-table-responsive tr {
                border: 1px solid #e9ecef;
                border-radius: 8px;
                margin-bottom: 16px;
                padding: 12px;
                background: #fff;
            }
            .cart-table-responsive td {
                padding: 8px 0;
                text-align: right;
                position: relative;
                padding-left: 40%;
                border: none;
            }
            .cart-table-responsive td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                top: 8px;
                width: 35%;
                font-weight: 600;
                text-align: left;
                color: #495057;
            }
            .cart-table-responsive td:first-child { padding-left: 0; text-align: center; }
            .cart-table-responsive td:first-child::before { display: none; }
        }

        /* Checkout summary sticky */
        .checkout-summary-sticky {
            position: sticky;
            top: 20px;
        }

        /* Checkout stepper */
        .checkout-stepper { max-width: 600px; margin: 0 auto; }
        .stepper-item { display: flex; flex-direction: column; align-items: center; flex: 0 0 auto; }
        .stepper-item span { font-size: 0.8125rem; margin-top: 6px; color: #adb5bd; }
        .stepper-item.active span { color: #495057; font-weight: 600; }
        .stepper-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e9ecef;
            color: #adb5bd;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stepper-item.active .stepper-circle { background: #b10100; color: #fff; }
        .stepper-line {
            flex: 1 1 60px;
            height: 2px;
            background: #e9ecef;
            margin: 0 8px;
            margin-bottom: 22px;
            max-width: 100px;
        }
        /* Brand color override */
        .text-brand, .product-price ins span, .primary-color { color: #b10100 !important; }
        .btn-primary, .bg-primary { background-color: #b10100 !important; border-color: #b10100 !important; }
        .btn-primary:hover, .btn-primary:focus { background-color: #7da210 !important; border-color: #7da210 !important; }
        .pro-count.blue { background-color: #b10100 !important; }
        .btn-outline-primary { color: #b10100; border-color: #b10100; }
        .btn-outline-primary:hover { background: #b10100; color: #fff; }
        .text-primary { color: #b10100 !important; }
        a { color: #b10100; }
        a:hover { color: #7da210; }
        .input-search-product:focus { border-color: #b10100 !important; }

        /* Hero banner */
        .hero-slide { min-height: 400px; display: flex; align-items: center; }
        .min-vh-50 { min-height: 350px; }
        @media (max-width: 768px) {
            .hero-slide { min-height: 280px; }
            .hero-slide h1 { font-size: 1.8rem !important; }
            .hero-slide .lead { font-size: 1rem !important; }
        }
        .carousel-indicators button { background-color: #fff; }

        /* Product filters sidebar */
        .product-filters {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
        }
        .filter-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        .filter-options { max-height: 220px; overflow-y: auto; padding-right: 4px; }
        .filter-options .form-check { margin-bottom: 6px; }

        @media (max-width: 991px) {
            .product-filters {
                position: fixed; top: 0; left: 0; bottom: 0;
                width: 320px; max-width: 90vw;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                overflow-y: auto;
                box-shadow: 4px 0 20px rgba(0,0,0,0.1);
                border-radius: 0;
                padding: 20px;
            }
            .product-filters.open { transform: translateX(0); }
            .filter-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1049; display: none; }
            .filter-overlay.open { display: block; }
        }

        .mobile-filters-toggle { display: none; }
        @media (max-width: 991px) {
            .mobile-filters-toggle {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #b10100;
                color: #fff;
                border: none;
                border-radius: 6px;
                padding: 8px 16px;
                font-weight: 600;
                cursor: pointer;
            }
        }
        body.filters-open { overflow: hidden; }

        /* Accessibility: focus visible */
        *:focus-visible {
            outline: 3px solid #b10100 !important;
            outline-offset: 2px !important;
        }
        button:focus, a:focus, input:focus, select:focus, textarea:focus {
            outline: 3px solid #b10100;
            outline-offset: 2px;
        }
        .sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0,0,0,0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Sticky add-to-cart */
        .sticky-add-to-cart {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid #dee2e6;
            padding: 12px 0;
            box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.08);
            z-index: 1030;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        .sticky-add-to-cart.visible { transform: translateY(0); }
        @media (max-width: 575px) {
            .sticky-add-to-cart .container > div { flex-wrap: wrap; gap: 8px; }
        }

        /* Flash sale countdown */
        .flash-countdown {
            display: inline-flex; align-items: center; gap: 4px;
            background: #1a2030; color: #fff;
            padding: 8px 14px; border-radius: 8px;
            font-family: 'Courier New', monospace;
        }
        .countdown-item { display: flex; flex-direction: column; align-items: center; min-width: 36px; }
        .countdown-value { font-size: 1.4rem; font-weight: 700; line-height: 1; }
        .countdown-label { font-size: 0.65rem; text-transform: uppercase; opacity: 0.7; }
        .countdown-sep { font-size: 1.2rem; padding: 0 2px; opacity: 0.5; align-self: flex-start; margin-top: 2px; }
        .flash-countdown.expired { background: #6c757d; }
    </style>

    @yield('meta')
    @stack('styles')

    <script type="application/ld+json">
    @verbatim
    {
        "@context": "https://schema.org",
        "@type": "Organization",
    @endverbatim
        "name": "{{ config('app.name') }}",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('logo.png') }}",
    @verbatim
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+1-234-567-890",
            "contactType": "customer service"
        }
    }
    @endverbatim
    </script>
</head>
<body class="wowy-template css_scrollbar template-index wrapper_full_width header_full_true header_sticky_true des_header_3 top_bar_true">

    @include('ecommerce::shop.partials._skip-link')

    <header class="header-area header-height-2">
        <div class="header-top header-top-ptb-1 d-none d-lg-block">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-xl-4 col-lg-4">
                        <div class="header-info">
                            <ul>
                                <li><i class="fa fa-phone-alt mr-5"></i><a href="tel:+1234567890">+1 234 567 890</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4 text-center">
                        <div id="news-flash" class="d-inline-block">
                            <ul>
                                <li>Bienvenido a nuestra tienda en linea</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4">
                        <div class="header-info header-info-right">
                            <ul class="d-flex align-items-center gap-3 justify-content-end mb-0">
                                <li>@include('ecommerce::shop.partials._language-switcher')</li>
                                @guest('ecommerce')
                                    <li><a href="{{ route('ecommerce.login') }}">{{ __('Iniciar sesion / Registrarse') }}</a></li>
                                @else
                                    <li><a href="{{ route('account.dashboard') }}">{{ auth('ecommerce')->user()->name }}</a></li>
                                @endguest
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-middle header-middle-ptb-1 d-none d-lg-block">
            <div class="container">
                <div class="header-wrap header-space-between">
                    <div class="logo logo-width-1">
                        <a href="{{ route('shop.index') }}">
                            <span style="font-size:1.6rem;font-weight:700;color:var(--color-heading);font-family:var(--font-text);">
                                {{ config('app.name', 'Tienda') }}
                            </span>
                        </a>
                    </div>
                    <div class="search-style-2">
                        <form action="{{ route('shop.index') }}" method="get" role="search">
                            <input type="text" name="q" class="input-search-product" placeholder="Buscar productos..." value="{{ request('q') }}" autocomplete="off" aria-label="Buscar productos">
                            <button type="submit" aria-label="Buscar"><i class="fa fa-search" aria-hidden="true"></i></button>
                        </form>
                    </div>
                    <div class="header-action-right">
                        <div class="header-action-2">
                            <div class="header-action-icon-2">
                                <a href="{{ route('ecommerce.wishlist.index') }}" class="wishlist-count">
                                    <img class="svgInject" alt="{{ __('Favoritos') }}" src="{{ asset('modules/ecommerce/images/icons/icon-heart.svg') }}">
                                </a>
                            </div>
                            <div class="header-action-icon-2">
                                <a class="mini-cart-icon" href="{{ route('cart.index') }}">
                                    <img alt="{{ __('Carrito') }}" src="{{ asset('modules/ecommerce/images/icons/icon-cart.svg') }}">
                                    <span class="pro-count blue js-cart-count">{{ app(\Modules\Ecommerce\Services\CartService::class)->count() }}</span>
                                </a>
                            </div>
                            <div class="header-action-icon-2">
                                <a href="{{ route('ecommerce.login') }}">
                                    <img alt="{{ __('Usuario') }}" src="{{ asset('modules/ecommerce/images/icons/icon-user.svg') }}">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-bottom gray-bg sticky-bar">
            <div class="container">
                <div class="header-wrap header-space-between position-relative main-nav">
                    <div class="logo logo-width-1 d-block d-lg-none">
                        <a href="{{ route('shop.index') }}">
                            <span style="font-size:1.4rem;font-weight:700;color:var(--color-heading);font-family:var(--font-text);">
                                {{ config('app.name', 'Tienda') }}
                            </span>
                        </a>
                    </div>

                    <div class="main-categories-wrap d-none d-lg-block">
                        <a class="categories-button-active open" href="#">
                            <span class="fa fa-list"></span> {{ __('Categorias') }} <i class="down far fa-chevron-down"></i> <i class="up far fa-chevron-up"></i>
                        </a>
                        <div class="categories-dropdown-wrap categories-dropdown-active-large default-open open">
                            <ul>
                                @php
                                    $headerCategories = \Modules\Ecommerce\Supports\ProductCategoryHelper::getNavigationCategories();
                                @endphp
                                @foreach($headerCategories as $headerCategory)
                                    <li>
                                        <a href="{{ route('shop.category', $headerCategory->slug) }}">
                                            {{ $headerCategory->name }}
                                            @if($headerCategory->children->count())
                                                <span class="menu-expand"><i class="down far fa-chevron-down"></i></span>
                                            @endif
                                        </a>
                                        @if($headerCategory->children->count())
                                            <ul class="dropdown" style="display:none">
                                                @foreach($headerCategory->children as $child)
                                                    <li><a href="{{ route('shop.category', $child->slug) }}">{{ $child->name }}</a></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="main-menu main-menu-padding-1 main-menu-lh-2 d-none d-lg-block main-menu-light hover-boder">
                        <nav aria-label="Navegación principal">
                            <ul class="navbar-nav flex-row">
                                <li class="nav-item"><a class="nav-link" href="{{ route('shop.index') }}">{{ __('Inicio') }}</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('cart.index') }}">{{ __('Carrito') }}</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('checkout.index') }}">{{ __('Checkout') }}</a></li>
                            </ul>
                        </nav>
                    </div>

                    <div class="header-action-right d-block d-lg-none">
                        <div class="header-action-2">
                            <div class="header-action-icon-2">
                                <a href="{{ route('ecommerce.wishlist.index') }}">
                                    <img alt="wowy" src="{{ asset('modules/ecommerce/images/icons/icon-heart.svg') }}">
                                </a>
                            </div>
                            <div class="header-action-icon-2">
                                <a class="mini-cart-icon" href="{{ route('cart.index') }}">
                                    <img alt="cart" src="{{ asset('modules/ecommerce/images/icons/icon-cart.svg') }}">
                                    <span class="pro-count white js-cart-count">{{ app(\Modules\Ecommerce\Services\CartService::class)->count() }}</span>
                                </a>
                            </div>
                            <div class="header-action-icon-2">
                                <a href="{{ route('ecommerce.login') }}">
                                    <img alt="wowy" src="{{ asset('modules/ecommerce/images/icons/icon-user.svg') }}">
                                </a>
                            </div>
                            <div class="header-action-icon-2 d-block d-lg-none">
                                <div class="burger-icon burger-icon">
                                    <span class="burger-icon-top"></span>
                                    <span class="burger-icon-mid"></span>
                                    <span class="burger-icon-bottom"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main" id="main-content" tabindex="-1">
        @yield('breadcrumb')
        @yield('content')
    </main>

    <footer class="main">
        @include('ecommerce::shop.partials.newsletter-form')

        <section class="section-padding-60">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <h5 class="widget-title">{{ config('app.name', 'Tienda') }}</h5>
                        <p class="font-sm text-muted">Tu tienda en linea de confianza.</p>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="widget-title">Enlaces</h5>
                        <nav aria-label="Footer links">
                        <ul class="footer-list">
                            <li><a href="{{ route('shop.index') }}">Inicio</a></li>
                            <li><a href="{{ route('cart.index') }}">Carrito</a></li>
                            <li><a href="{{ route('checkout.index') }}">Checkout</a></li>
                            <li><a href="{{ route('shop.legal.show', 'contact') }}">Contacto</a></li>
                        </ul>
                        </nav>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="widget-title">Cuenta</h5>
                        <ul class="footer-list">
                            <li><a href="{{ route('ecommerce.login') }}">Iniciar sesion</a></li>
                            <li><a href="{{ route('ecommerce.register') }}">Registrarse</a></li>
                            <li><a href="{{ route('account.dashboard') }}">Mi cuenta</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="widget-title">Información</h5>
                        <ul class="footer-list">
                            <li><a href="{{ route('shop.legal.show', 'terms') }}">Términos y condiciones</a></li>
                            <li><a href="{{ route('shop.legal.show', 'privacy') }}">Política de privacidad</a></li>
                            <li><a href="{{ route('shop.legal.show', 'shipping-policy') }}">Política de envíos</a></li>
                            <li><a href="{{ route('shop.legal.show', 'returns-policy') }}">Política de devoluciones</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="widget-title">Contacto</h5>
                        <ul class="contact-info">
                            <li><i class="fa fa-phone-alt mr-5"></i> +1 234 567 890</li>
                            <li><i class="fa fa-envelope mr-5"></i> info@example.com</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <div class="container py-3 border-top">
            <div class="row align-items-center text-center text-md-start">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="small mb-2 text-muted fw-bold">Métodos de pago aceptados:</p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                        <span class="badge bg-light text-dark border" title="Visa"><i class="fab fa-cc-visa fa-2x text-primary"></i></span>
                        <span class="badge bg-light text-dark border" title="Mastercard"><i class="fab fa-cc-mastercard fa-2x text-danger"></i></span>
                        <span class="badge bg-light text-dark border" title="Amex"><i class="fab fa-cc-amex fa-2x text-info"></i></span>
                        <span class="badge bg-light text-dark border" title="PSE"><i class="fas fa-university fa-2x text-secondary"></i></span>
                        <span class="badge bg-light text-dark border" title="Nequi"><i class="fas fa-mobile-alt fa-2x text-success"></i></span>
                        <span class="badge bg-light text-dark border" title="Efectivo"><i class="fas fa-money-bill fa-2x text-success"></i></span>
                    </div>
                </div>
                <div class="col-md-6 mb-3 mb-md-0 text-md-end">
                    <p class="small mb-2 text-muted fw-bold">Síguenos:</p>
                    <div class="d-flex gap-3 justify-content-center justify-content-md-end">
                        <a href="#" target="_blank" rel="noopener" aria-label="Facebook" class="text-muted fs-4"><i class="fab fa-facebook"></i></a>
                        <a href="#" target="_blank" rel="noopener" aria-label="Instagram" class="text-muted fs-4"><i class="fab fa-instagram"></i></a>
                        <a href="#" target="_blank" rel="noopener" aria-label="Twitter" class="text-muted fs-4"><i class="fab fa-x-twitter"></i></a>
                        <a href="#" target="_blank" rel="noopener" aria-label="YouTube" class="text-muted fs-4"><i class="fab fa-youtube"></i></a>
                        <a href="#" target="_blank" rel="noopener" aria-label="LinkedIn" class="text-muted fs-4"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="row mt-3 pt-3 border-top text-center">
                <div class="col-12">
                    <span class="badge bg-success me-2"><i class="fas fa-shield-alt me-1"></i>Compra segura SSL</span>
                    <span class="badge bg-info me-2"><i class="fas fa-truck me-1"></i>Envíos a todo el país</span>
                    <span class="badge bg-warning text-dark me-2"><i class="fas fa-undo me-1"></i>30 días para devoluciones</span>
                    <span class="badge bg-secondary"><i class="fas fa-headset me-1"></i>Soporte 24/7</span>
                </div>
            </div>
        </div>

        <div class="container pb-20">
            <div class="row">
                <div class="col-12 mb-20">
                    <div class="footer-bottom"></div>
                </div>
                <div class="col-lg-6">
                    <p class="float-md-left font-sm text-muted mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'Alsernet') }}. Todos los derechos reservados.</p>
                </div>
                <div class="col-lg-6">
                    <p class="text-lg-end text-center font-sm text-muted mb-0">{{ __('Todos los derechos reservados.') }}</p>
                </div>
            </div>
        </div>
    </footer>

    <div id="scrollUp"><i class="fal fa-long-arrow-up"></i></div>

    <script src="{{ asset('modules/ecommerce/js/vendor/jquery.min.js') }}"></script>
    <script src="{{ asset('modules/ecommerce/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('modules/ecommerce/js/main.js') }}"></script>

    <script>
        window.siteUrl = '{{ url("/") }}';
    </script>

    @stack('scripts')
    @include('ecommerce::shop.partials._filters-script')
    @include('ecommerce::shop.partials._form-spinner-script')
    @include('ecommerce::shop.partials.cart-ajax-script')
    @include('ecommerce::shop.partials.newsletter-script')
    @include('ecommerce::shop.partials.restock-alert-script')
    @include('ecommerce::shop.partials._whatsapp-button')
    @include('ecommerce::shop.partials._cookie-banner')
    @include('ecommerce::shop.partials._quick-view')
    @include('ecommerce::shop.partials._mini-cart-drawer')
    @include('ecommerce::shop.partials._search-autocomplete-script')
    @include('ecommerce::shop.partials._compare-floating')
    @include('ecommerce::shop.partials._analytics-events-script')
    @include('ecommerce::shop.partials._welcome-popup')
    @include('ecommerce::shop.partials._pwa-register')
    @include('ecommerce::shop.partials._chatbot-widget')
    @include('ecommerce::shop.partials._exit-intent-popup')
    @include('ecommerce::shop.partials._countdown-script')
</body>
</html>
