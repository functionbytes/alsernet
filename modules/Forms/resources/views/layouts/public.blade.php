{{--
    Layout de las páginas públicas del constructor de formularios.

    En el proyecto de origen estas vistas extendían 'template::layouts.default',
    el layout del sitio que servía el módulo Template. Aquí no hay front-end
    público que lo aporte, así que el módulo trae el suyo: HTML mínimo con
    Bootstrap y los assets de Forms, sin cabecera ni pie del panel.

    Secciones: title, css, content, js.
--}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="@yield('robots', 'noindex,nofollow')">
    <title>@yield('title', config('app.name'))</title>

    <link rel="stylesheet" href="{{ asset('core/bootstrap/css/bootstrap.min.css') }}">
    @yield('css')
</head>
<body class="bg-light">
@yield('content')

<script src="{{ asset('core/js/jquery-3.6.4.min.js') }}"></script>
<script src="{{ asset('core/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@yield('js')
</body>
</html>
