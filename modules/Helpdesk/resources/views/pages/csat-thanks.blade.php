<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Gracias!</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background:#f7f7fa; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .csat-card { max-width:480px; border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.08); }
    </style>
</head>
<body>
    <div class="card csat-card mx-3">
        <div class="card-body p-4 p-md-5 text-center">
            <i class="fas fa-circle-check text-success" style="font-size:3.5rem"></i>
            <h3 class="mt-3 mb-2">¡Gracias por tu valoración!</h3>
            <p class="text-muted mb-0">
                Tu calificación de <strong>{{ str_repeat('⭐', $rating) }} ({{ $rating }}/5)</strong> nos ayuda a mejorar.
            </p>
        </div>
    </div>
</body>
</html>
