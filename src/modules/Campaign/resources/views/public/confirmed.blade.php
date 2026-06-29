<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Suscripción confirmada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5 text-center" style="max-width:560px">
        <h2>✅ Suscripción confirmada</h2>
        <p class="lead">{{ $subscriber->email }} ha sido confirmado correctamente.</p>
        <a href="{{ route('campaign.manage', $subscriber->uid) }}" class="btn btn-link">Gestionar mis preferencias</a>
    </div>
</body>
</html>
