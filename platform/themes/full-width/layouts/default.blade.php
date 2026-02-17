<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ get_text_direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', config('app.name'))">
    <meta name="keywords" content="@yield('keywords')">
    <title>@yield('title', config('app.name'))</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap{{ is_rtl() ? '.rtl' : '' }}.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Theme CSS -->
    <link href="{{ theme_asset('css/style.css') }}" rel="stylesheet">
    @if (is_rtl())
        <link href="{{ theme_asset('css/rtl.css') }}" rel="stylesheet">
    @endif

    @yield('css')
</head>
<body class="{{ rtl_class() }}">
    <!-- Preloader -->
    @include('template::partials.preloader')

    <!-- Header -->
    @include('template::partials.header')

    <!-- Breadcrumb -->
    @if (Route::has('breadcrumb'))
        @include('template::partials.breadcrumb')
    @endif

    <!-- Main Content -->
    <main class="main-content" id="main-section">
        <div class="container">
            <div class="row">
                @if (has_sidebar())
                    <!-- Sidebar (Left/Right depending on RTL) -->
                    <aside class="col-lg-3 sidebar sidebar-{{ get_sidebar_position() }}">
                        @include('template::partials.sidebar')
                    </aside>

                    <!-- Content -->
                    <div class="col-lg-9">
                        @yield('content')
                    </div>
                @else
                    <!-- Full Width Content -->
                    <div class="col-12">
                        @yield('content')
                    </div>
                @endif
            </div>
        </div>
    </main>

    <!-- Footer -->
    @include('template::partials.footer')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery (opcional) -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <!-- Theme JS -->
    <script src="{{ theme_asset('js/main.js') }}"></script>

    @yield('js')

    <!-- Analytics -->
    @if (config('services.google_analytics.enabled'))
        <!-- Google Analytics -->
    @endif
</body>
</html>