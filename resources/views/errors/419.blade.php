@php
    try {
        $errorPageId = setting('error-page-419');
        $errorPage = $errorPageId
            ? \Modules\Page\Models\Page::find($errorPageId)
            : \Modules\Page\Models\Page::findByPageType('error_419');
    } catch (\Throwable) {
        $errorPage = null;
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $errorPage?->title ?? '419 - Sesión expirada' }}</title>
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/libs/fontawesome/fontawesome.css') }}">
    <style>
        body { background: #f5f6f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: 'Nunito Sans', sans-serif; }
        .error-card { background: #fff; border-radius: 12px; padding: 3rem 2.5rem; text-align: center; max-width: 480px; width: 90%; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .error-code { font-size: 5rem; font-weight: 800; color: #90bb13; line-height: 1; }
        .error-icon { font-size: 4rem; color: #90bb13; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="error-card">
        @if ($errorPage?->content)
            {!! $errorPage->content !!}
        @else
            <div class="error-icon"><i class="fas fa-clock"></i></div>
            <div class="error-code">419</div>
            <h4 class="mt-3 mb-2 fw-bold">Sesión expirada</h4>
            <p class="text-muted mb-4">Tu sesión ha expirado. Por favor recarga la página e inténtalo de nuevo.</p>
            <button onclick="location.reload()" class="btn btn-primary me-2">
                <i class="fas fa-sync-alt me-1"></i> Recargar página
            </button>
            <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                <i class="fas fa-home me-1"></i> Inicio
            </a>
        @endif
    </div>
</body>
</html>
