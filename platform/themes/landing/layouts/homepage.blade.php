<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ get_text_direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', config('app.name'))">
    <title>@yield('title', config('app.name'))</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap{{ is_rtl() ? '.rtl' : '' }}.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Theme CSS -->
    <link href="{{ theme_asset('css/style.css') }}" rel="stylesheet">
    @if (is_rtl())
        <link href="{{ theme_asset('css/rtl.css') }}" rel="stylesheet">
    @endif

    <!-- Cookie Consent CSS -->
    <link href="/modules/cookie/css/cookie-consent.css" rel="stylesheet">

    @yield('css')
</head>
<body class="{{ rtl_class() }}">
    <!-- Preloader -->
    @include('template::partials.preloader')

    <!-- Header -->
    @include('template::partials.header')

    <!-- Main Content (Full Width) -->
    <main class="main-content homepage" id="main-section">
        @yield('content')
    </main>

    <!-- Footer -->
    @include('template::partials.footer')

    <!-- Cookie Consent Banner -->
    @include('cookie::index')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <!-- Cookie Consent JS -->
    <script src="/modules/cookie/js/cookie-consent.js"></script>

    <!-- Theme JS -->
    <script src="{{ theme_asset('js/main.js') }}"></script>

    @yield('js')
</body>
</html>
