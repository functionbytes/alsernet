{{--
    Página pública de confirmación de baja. No extiende el layout del panel:
    la ve un cliente sin sesión, desde su correo.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Baja confirmada · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h1 class="h4 fw-bold mb-3">Baja confirmada</h1>
                        <p class="mb-2">
                            No volveremos a enviar felicitaciones de cumpleaños a
                            <strong>{{ $email }}</strong>.
                        </p>
                        <p class="text-muted small mb-0">
                            Esto no afecta a los correos sobre tus pedidos ni a las respuestas
                            de nuestro equipo de atención al cliente.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
