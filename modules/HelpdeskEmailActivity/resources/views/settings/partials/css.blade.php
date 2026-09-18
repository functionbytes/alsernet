{{-- CSS del módulo (mismo patrón que emails/index.blade.php): fuentes de
     Google (Inter + JetBrains Mono) y las 2 hojas del módulo, con
     ?v=filemtime para que el navegador no sirva una versión cacheada tras
     cada cambio. Compartido por las 4 pantallas secundarias vía @include
     para no repetir este bloque en cada una. --}}
@php
    $emaillogCss = public_path('modules/helpdeskemailactivity/css/emaillog.css');
    $emaillogExtrasCss = public_path('modules/helpdeskemailactivity/css/emaillog-extras.css');
    $emaillogCssV = is_file($emaillogCss) ? filemtime($emaillogCss) : time();
    $emaillogExtrasCssV = is_file($emaillogExtrasCss) ? filemtime($emaillogExtrasCss) : time();
@endphp

@push('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemailactivity/css/emaillog.css') }}?v={{ $emaillogCssV }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemailactivity/css/emaillog-extras.css') }}?v={{ $emaillogExtrasCssV }}">
@endpush
