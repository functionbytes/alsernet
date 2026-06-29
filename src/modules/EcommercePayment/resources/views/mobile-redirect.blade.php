<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="0;url={{ $returnUrl }}">
    <title>{{ $success ? 'Pago confirmado' : 'Pago' }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; padding: 40px 20px; text-align: center; background: #f8f9fa; }
        .card { max-width: 420px; margin: 0 auto; background: #fff; padding: 32px 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        h1 { color: {{ $success ? '#13c672' : '#fa896b' }}; margin: 0 0 12px; font-size: 22px; }
        p { color: #666; line-height: 1.5; }
        a { color: #90bb13; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $success ? '¡Pago confirmado!' : 'Pago no completado' }}</h1>
        <p>{{ $message }}</p>
        <p>Redirigiendo a la app…</p>
        <p><a href="{{ $returnUrl }}">Si no se abre automáticamente, toca aquí</a></p>
    </div>
    <script>setTimeout(function () { window.location = @json($returnUrl); }, 100);</script>
</body>
</html>
