@php
    try {
        $errorPageId = setting('error-page-500');
        $errorPage = $errorPageId
            ? \Modules\Page\Models\Page::find($errorPageId)
            : \Modules\Page\Models\Page::findByPageType('error_500');
    } catch (\Throwable) {
        $errorPage = null;
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $errorPage?->title ?? '500 - Error interno del servidor' }}</title>
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/libs/fontawesome/fontawesome.css') }}">
    <style>
        body { background: #f5f6f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: 'Nunito Sans', sans-serif; }
        .error-card { background: #fff; border-radius: 12px; padding: 3rem 2.5rem; text-align: center; max-width: 480px; width: 90%; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .error-code { font-size: 5rem; font-weight: 800; color: #FA896B; line-height: 1; }
        .error-icon { font-size: 4rem; color: #FA896B; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="error-card">
        @if ($errorPage?->content)
            {!! $errorPage->content !!}
        @else
            <div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="error-code">500</div>
            <h4 class="mt-3 mb-2 fw-bold">Error interno del servidor</h4>
            <p class="text-muted mb-4">Ha ocurrido un error inesperado. Nuestro equipo ha sido notificado.</p>
            <a href="{{ url('/') }}" class="btn btn-primary me-2">
                <i class="fas fa-home me-1"></i> Inicio
            </a>
            <button onclick="history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </button>
        @endif
    </div>
</body>
</html>
