<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Suscripción cancelada</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 520px; margin: 80px auto; padding: 32px; text-align: center; color: #333; }
        h1 { color: #90bb13; margin-bottom: 16px; }
        p { line-height: 1.6; color: #555; }
        .icon { font-size: 48px; margin-bottom: 24px; }
        .email { font-weight: 600; color: #90bb13; }
        .footer { margin-top: 48px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="icon">✉️</div>

    @if (! empty($email))
        <h1>Te has dado de baja correctamente</h1>
        <p>Hemos eliminado <span class="email">{{ $email }}</span> de nuestra lista de suscriptores.</p>
        <p>No recibirás más comunicaciones de marketing por nuestra parte.</p>
    @else
        <h1>Enlace inválido</h1>
        <p>El enlace de baja ha caducado o no es válido. Si quieres dejar de recibir nuestros emails, contesta a cualquiera de nuestros correos pidiendo la baja.</p>
    @endif

    <div class="footer">
        Si no fuiste tú quien solicitó esta baja, puedes ignorar este mensaje.
    </div>
</body>
</html>
