<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if ($isMobile)
        <meta http-equiv="refresh" content="0;url={{ $deepLink }}">
    @endif
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; padding: 0; background: #f8f9fa; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { max-width: 440px; width: calc(100% - 32px); margin: 16px; background: #fff; padding: 32px 28px; border-radius: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); text-align: center; }
        h1 { color: #333; margin: 0 0 12px; font-size: 22px; }
        p { color: #666; line-height: 1.55; margin: 12px 0; }
        .btn { display: inline-block; background: #b10100; color: #fff !important; padding: 14px 28px; border-radius: 10px; text-decoration: none; font-weight: 600; margin: 12px 4px; }
        .btn-secondary { background: transparent; color: #666 !important; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $title }}</h1>
        @if ($isMobile)
            <p>Abriendo la aplicación…</p>
            <p><a href="{{ $deepLink }}" class="btn">Abrir Inoqualab</a></p>
            <p><a href="{{ $webFallback }}" class="btn btn-secondary">Continuar en el navegador</a></p>
            <script>setTimeout(function () { window.location = @json($deepLink); }, 100);</script>
        @else
            <p>Tu solicitud está lista. Continúa en el navegador o abre la app.</p>
            <p><a href="{{ $webFallback }}" class="btn">Continuar en el navegador</a></p>
            <p><a href="{{ $deepLink }}" class="btn btn-secondary">Abrir Inoqualab</a></p>
        @endif
    </div>
</body>
</html>
