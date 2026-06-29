<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gracias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5 text-center" style="max-width:560px">
        <h2>¡Gracias!</h2>
        @if (session('info'))
            <p class="lead">{{ session('info') }}</p>
        @else
            <p class="lead">Tu suscripción a {{ $list->name }} ha sido procesada.</p>
        @endif
    </div>
</body>
</html>
