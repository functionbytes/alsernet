<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @yield('css')
</head>
<body>
    <header>
        @include('template::partials.header')
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        @include('template::partials.footer')
    </footer>

    <script src="{{ asset('js/app.js') }}"></script>
    @yield('js')
</body>
</html>