<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->name }}</title>
    {{-- Assets self-hosted, no CDN: esta página se sirve desde el panel y no
         debe depender de jsdelivr/cdnjs/code.jquery para renderizar ni validar
         (mismo criterio que public.render con Validate y Select2). --}}
    <link rel="stylesheet" href="{{ asset('core/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('libs/fontawesome/fontawesome.css') }}">
</head>
<body class="p-3 bg-white">
    @include('forms::public.render', ['form' => $form, 'shortcodeConfig' => ['display' => 'inline', 'show_title' => true]])
    <script src="{{ asset('core/js/jquery-3.6.4.min.js') }}"></script>
    <script src="{{ asset('core/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
