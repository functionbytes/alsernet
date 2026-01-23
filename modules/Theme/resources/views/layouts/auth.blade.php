<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="">
    <meta name="description" content="">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:site" content="@publisher_handle">

    @yield('head')

    <link rel="stylesheet" href="{{ themeAsset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('css/auth.css') }}">


    @stack('css')

</head>

<body>

    <div id="page" class="page font--jakarta">

        @yield('content')

    </div>

    <script src="{{ themeAsset('libs/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ themeAsset('libs/select2/dist/js/select2.min.js') }}"></script>
    <script src="{{ themeAsset('libs/jquery-validation/dist/jquery.validate.min.js') }}"></script>

    @stack('scripts')

</body>

</html>
