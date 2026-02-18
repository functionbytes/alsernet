<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', config('app.name'))">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Slick Carousel -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css">

    <!-- Wowy Custom Styles -->
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}">

    <!-- Cookie Consent CSS -->
    <link rel="stylesheet" href="/vendor/modules/Cookie/css/cookie-consent.css">

    <!-- Custom CSS from settings -->
    @php $customCss = setting('theme.custom_css'); @endphp
    @if($customCss)
        <style id="theme-custom-css">{{ $customCss }}</style>
    @endif

    <!-- Custom Header HTML from settings -->
    @php $customHeaderHtml = setting('theme.custom_header_html'); @endphp
    @if($customHeaderHtml)
        {!! $customHeaderHtml !!}
    @endif

    <!-- Custom Header JS from settings -->
    @php $customHeaderJs = setting('theme.custom_header_js'); @endphp
    @if($customHeaderJs)
        <script>{!! $customHeaderJs !!}</script>
    @endif

    @yield('css')
</head>
<body class="wowy-template">
    <!-- Top Bar -->
    <div class="top-bar bg-light py-2">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted small">{{ theme_trans('welcome_message') }}</p>
                </div>
                <div class="col-md-6 text-end">
                    @if (auth()->check())
                        <a href="/dashboard" class="text-decoration-none small">{{ auth()->user()->name }}</a>
                        <span class="text-muted small">|</span>
                        <a href="/logout" class="text-decoration-none small">{{ theme_trans('logout') }}</a>
                    @else
                        <a href="/login" class="text-decoration-none small">{{ theme_trans('login') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="main-header sticky-top bg-white border-bottom">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light py-3">
                <!-- Logo -->
                <a class="navbar-brand fw-bold" href="{{ url('/') }}">
                    {{ config('app.name') }}
                </a>

                <!-- Toggle Button -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation Menu -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/') }}">{{ theme_trans('home') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/about') }}">{{ theme_trans('about_us') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/contact') }}">{{ theme_trans('contact') }}</a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <main>
