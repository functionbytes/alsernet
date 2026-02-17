<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ get_text_direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap{{ is_rtl() ? '.rtl' : '' }}.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="{{ theme_asset('css/style.css') }}" rel="stylesheet">
    @if (is_rtl())
        <link href="{{ theme_asset('css/rtl.css') }}" rel="stylesheet">
    @endif
    @yield('css')
</head>
<body class="{{ rtl_class() }}">
    @include('template::partials.preloader')
    @include('template::partials.header')
    @if (Route::has('breadcrumb'))
        @include('template::partials.breadcrumb')
    @endif
    <main class="main-content product-right-sidebar" id="main-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-9 product-content">
                    @yield('content')
                </div>
                <aside class="col-lg-3 sidebar sidebar-right product-filters">
                    @include('template::partials.sidebar')
                </aside>
            </div>
        </div>
    </main>
    @include('template::partials.footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="{{ theme_asset('js/main.js') }}"></script>
    @yield('js')
</body>
</html>
