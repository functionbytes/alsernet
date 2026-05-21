<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Gracias por ser un cliente VIP!</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333333;">

    <div style="text-align: center; margin-bottom: 24px;">
        <div style="background: #b10100; padding: 16px; border-radius: 6px 6px 0 0;">
            <span style="color: white; font-size: 20px; font-weight: bold;">¡Eres un cliente VIP!</span>
        </div>
    </div>

    <h2 style="margin: 0 0 12px;">Hola {{ $customer->first_name ?? '' }}</h2>
    <p style="color: #555555; margin-bottom: 20px;">
        Has alcanzado los <strong>€{{ number_format($milestone, 0, ',', '.') }}</strong> en compras acumuladas.
        Queremos agradecerte tu fidelidad con una recompensa exclusiva.
    </p>

    <div style="border: 2px solid #b10100; border-radius: 6px; padding: 24px; margin-bottom: 24px; text-align: center;">
        <div style="font-size: 48px; margin-bottom: 12px;">&#127942;</div>
        <p style="margin: 0 0 8px; font-size: 18px; font-weight: bold; color: #b10100;">
            Cliente VIP — Nivel €{{ number_format($milestone, 0, ',', '.') }}
        </p>
        <p style="margin: 0; color: #555555;">
            Has acumulado un total de <strong>€{{ number_format($newLifetime, 2, ',', '.') }}</strong> en compras.
            Gracias por confiar en nosotros.
        </p>
    </div>

    <div style="background: #f8f8f8; border-radius: 6px; padding: 20px; margin-bottom: 24px;">
        <p style="margin: 0 0 8px; font-weight: bold;">Tu beneficio exclusivo:</p>
        <p style="margin: 0; color: #555555;">
            Contacta con nuestro equipo para recibir tu descuento especial VIP en tu próxima compra.
        </p>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="#" style="display: inline-block; background: #b10100; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
            Explorar tienda
        </a>
    </div>

    <p style="font-size: 12px; color: #999999; text-align: center; margin-top: 32px;">
        Si no deseas recibir estas comunicaciones, puedes
        <a href="#" style="color: #999999;">darte de baja</a> en cualquier momento.
    </p>

</body>
</html>
