@php
    try {
        $errorPageId = setting("error-page-422");
        $errorPage = $errorPageId
            ? \Modules\Page\Models\Page::find($errorPageId)
            : \Modules\Page\Models\Page::findByPageType("error_422");
    } catch (\Throwable) {
        $errorPage = null;
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $errorPage?->title ?? 'Error 422' }}</title>
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/libs/fontawesome/fontawesome.css') }}">
    <style>
        body { background: #f5f6f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: 'Nunito Sans', sans-serif; }
        .error-card { background: #fff; border-radius: 12px; padding: 3rem 2.5rem; text-align: center; max-width: 480px; width: 90%; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .error-code { font-size: 5rem; font-weight: 800; color: #b10100; line-height: 1; }
        .error-icon { font-size: 4rem; color: #b10100; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="error-card">
        @if ($errorPage?->content)
            {!! $errorPage->content !!}
        @else
            <div class="error-code">422</div>
            <h4 class="mt-3 mb-2 fw-bold">Error 422</h4>
            <p class="text-muted mb-4">Ha ocurrido un error al procesar tu solicitud.</p>
            <a href="{{ url('/') }}" class="btn btn-primary me-2"><i class="fas fa-home me-1"></i> Inicio</a>
            <button onclick="history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Volver</button>
        @endif
    </div>
</body>
</html>
