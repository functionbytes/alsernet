<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {!! \BaseHelper::googleFonts('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap') !!}

    <style>
        :root {
            --font-text: {{ theme_option('font_text', 'Poppins') }}, sans-serif;
            --color-brand: {{ theme_option('color_brand', '#5897fb') }};
            --primary-color: {{ theme_option('color_brand', '#5897fb') }};
            --color-brand-2: {{ theme_option('color_brand_2', '#3256e0') }};
            --color-primary: {{ theme_option('color_primary', '#3f81eb') }};
            --color-secondary: {{ theme_option('color_secondary', '#41506b') }};
            --color-warning: {{ theme_option('color_warning', '#ffb300') }};
            --color-danger: {{ theme_option('color_danger', '#ff3551') }};
            --color-success: {{ theme_option('color_success', '#3ed092') }};
            --color-info: {{ theme_option('color_info', '#18a1b7') }};
            --color-text: {{ theme_option('color_text', '#4f5d77') }};
            --color-heading: {{ theme_option('color_heading', '#222222') }};
            --color-grey-1: {{ theme_option('color_grey_1', '#111111') }};
            --color-grey-2: {{ theme_option('color_grey_2', '#242424') }};
            --color-grey-4: {{ theme_option('color_grey_4', '#90908e') }};
            --color-grey-9: {{ theme_option('color_grey_9', '#f8f9fa') }};
            --color-muted: {{ theme_option('color_muted', '#8e8e90') }};
            --color-body: {{ theme_option('color_body', '#4f5d77') }};
        }
    </style>

    {!! Theme::header() !!}

    @php
        $cartCount = app(\Modules\Ecommerce\Services\CartService::class)->count();
        $headerStyle = theme_option('header_style') ?: '';
        $page = Theme::get('page');
        if ($page && method_exists($page, 'getMetaData')) {
            $headerStyle = $page->getMetaData('header_style', true) ?: $headerStyle;
        }
        $headerStyle = ($headerStyle && in_array($headerStyle, array_keys(get_layout_header_styles()))) ? $headerStyle : '';
    @endphp
</head>
<body {!! Theme::bodyAttributes() !!} class="@if (\BaseHelper::isRtlEnabled()) rtl @endif header_full_true wowy-template css_scrollbar template-index wrapper_full_width header_full_true header_sticky_true des_header_3 top_bar_true">
{!! apply_filters(THEME_FRONT_BODY, null) !!}
<div id="alert-container"></div>

{!! Theme::partial('preloader') !!}

<header class="header-area header-height-2 {{ $headerStyle }}">
    <div class="header-top header-top-ptb-1 d-none d-lg-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-xl-2 col-lg-2">
                    <div class="header-info">
                        <ul>
                            @if ($hotline = theme_option('hotline'))
                                <li><i class="fa fa-phone-alt mr-5"></i><a href="tel:{{ $hotline }}">{{ $hotline }}</a></li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="col-xl-8 col-lg-8 text-center">
                    @if (theme_option('header_messages') && $headerMessages = json_decode(theme_option('header_messages'), true))
                        <div id="news-flash" class="d-inline-block">
                            <ul>
                                @foreach($headerMessages as $headerMessage)
                                    @if (count($headerMessage) == 4)
                                        <li>
                                            @if ($headerMessage[0]['value'])
                                                {!! \BaseHelper::renderIcon($headerMessage[0]['value'], null, ['class' => 'd-inline-block mr-5']) !!}
                                            @endif
                                            @if ($headerMessage[1]['value'])
                                                <span class="d-inline-block">{!! clean($headerMessage[1]['value']) !!}</span>
                                            @endif
                                            @if ($headerMessage[2]['value'] && $headerMessage[3]['value'])
                                                &nbsp;<a class="active d-inline-block" href="{{ url($headerMessage[2]['value']) }}">{!! clean($headerMessage[3]['value']) !!}</a>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="col-xl-2 col-lg-2">
                    <div class="header-info header-info-right">
                        <ul>
                            @guest('ecommerce')
                                <li><a href="{{ route('ecommerce.login') }}">{{ __('Iniciar sesion / Registrarse') }}</a></li>
                            @else
                                <li><a href="#">{{ auth('ecommerce')->user()->name }}</a></li>
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
                    <a href="{{ url('/') }}">
                        @if (theme_option('logo'))
                            <img src="{{ \RvMedia::getImageUrl(theme_option('logo')) }}" alt="{{ theme_option('site_title') }}">
                        @else
                            <span style="font-size:1.6rem;font-weight:700;color:var(--color-heading);font-family:var(--font-text);">{{ config('app.name') }}</span>
                        @endif
                    </a>
                </div>

                <div class="search-style-2">
                    <form action="{{ route('shop.index') }}" method="get">
                        <input type="text" name="q" class="input-search-product" placeholder="{{ __('Buscar productos...') }}" value="{{ request('q') }}" autocomplete="off">
                        <button type="submit" title="search"><i class="fa fa-search"></i></button>
                    </form>
                </div>

                <div class="header-action-right">
                    <div class="header-action-2">
                        <div class="header-action-icon-2">
                            <a href="{{ route('ecommerce.wishlist.index') }}" class="wishlist-count">
                                <img class="svgInject" alt="{{ __('Favoritos') }}" src="{{ theme_asset('images/icons/icon-heart.svg') }}">
                            </a>
                        </div>
                        <div class="header-action-icon-2">
                            <a class="mini-cart-icon" href="{{ route('cart.index') }}">
                                <img alt="{{ __('Carrito') }}" src="{{ theme_asset('images/icons/icon-cart.svg') }}">
                                <span class="pro-count blue">{{ $cartCount }}</span>
                            </a>
                            <div class="cart-dropdown-wrap cart-dropdown-hm2">
                                {!! Theme::partial('cart-panel') !!}
                            </div>
                        </div>
                        <div class="header-action-icon-2">
                            <a href="{{ route('ecommerce.login') }}">
                                <img alt="{{ __('Usuario') }}" src="{{ theme_asset('images/icons/icon-user.svg') }}">
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
                    <a href="{{ url('/') }}">
                        @if (theme_option('logo'))
                            <img src="{{ \RvMedia::getImageUrl(theme_option('logo')) }}" alt="{{ theme_option('site_title') }}">
                        @else
                            <span style="font-size:1.4rem;font-weight:700;color:var(--color-heading);font-family:var(--font-text);">{{ config('app.name') }}</span>
                        @endif
                    </a>
                </div>

                <div class="main-categories-wrap d-none d-lg-block">
                    <a class="categories-button-active open" href="#">
                        <span class="fa fa-list"></span> {{ __('Categorias') }} <i class="down far fa-chevron-down"></i> <i class="up far fa-chevron-up"></i>
                    </a>
                    <div class="categories-dropdown-wrap categories-dropdown-active-large default-open open">
                        <ul>
                            @php
                                $categories = \Modules\Ecommerce\Models\ProductCategory::query()
                                    ->where('parent_id', 0)
                                    ->where('status', 'published')
                                    ->orderBy('order')
                                    ->with('children')
                                    ->get();
                            @endphp
                            @foreach($categories as $category)
                                <li>
                                    <a href="{{ route('shop.category', $category->slug) }}">
                                        {{ $category->name }}
                                        @if($category->children->count())
                                            <span class="menu-expand"><i class="down far fa-chevron-down"></i></span>
                                        @endif
                                    </a>
                                    @if($category->children->count())
                                        <ul class="dropdown" style="display:none">
                                            @foreach($category->children as $child)
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
                    <nav>
                        {!! Menu::renderMenuLocation('main-menu', ['view' => 'main-menu']) !!}
                    </nav>
                </div>

                @if ($hotline = theme_option('hotline'))
                    <div class="hotline d-none d-lg-block">
                        <p><i class="fa fa-phone-alt"></i><span>{{ __('Hotline') }}</span> <a href="tel:{{ $hotline }}" class="text">{{ $hotline }}</a></p>
                    </div>
                @endif

                <div class="header-action-right d-block d-lg-none">
                    <div class="header-action-2">
                        <div class="header-action-icon-2">
                            <a href="{{ route('ecommerce.wishlist.index') }}">
                                <img alt="wowy" src="{{ theme_asset('images/icons/icon-heart.svg') }}">
                            </a>
                        </div>
                        <div class="header-action-icon-2">
                            <a class="mini-cart-icon" href="{{ route('cart.index') }}">
                                <img alt="cart" src="{{ theme_asset('images/icons/icon-cart.svg') }}">
                                <span class="pro-count white">{{ $cartCount }}</span>
                            </a>
                        </div>
                        <div class="header-action-icon-2">
                            <a href="{{ route('ecommerce.login') }}">
                                <img alt="wowy" src="{{ theme_asset('images/icons/icon-user.svg') }}">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
