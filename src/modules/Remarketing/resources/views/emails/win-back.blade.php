<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Te echamos de menos</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333333;">

    <div style="text-align: center; margin-bottom: 24px;">
        <div style="background: #90bb13; padding: 16px; border-radius: 6px 6px 0 0;">
            <span style="color: white; font-size: 20px; font-weight: bold;">Te echamos de menos</span>
        </div>
    </div>

    <h2 style="margin: 0 0 12px;">Hola {{ $customer->first_name ?? '' }}</h2>
    <p style="color: #555555; margin-bottom: 24px;">
        Ha pasado un tiempo desde que no te vemos por aquí. Para celebrar tu vuelta, tenemos un descuento exclusivo esperándote.
    </p>

    <div style="border: 2px dashed #90bb13; border-radius: 6px; padding: 24px; margin-bottom: 24px; text-align: center;">
        <div style="font-size: 13px; color: #999999; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">
            Tu descuento exclusivo
        </div>
        <div style="font-size: 36px; font-weight: bold; color: #90bb13; letter-spacing: 2px;">
            VUELVE10
        </div>
        <div style="font-size: 14px; color: #555555; margin-top: 8px;">
            10% de descuento en tu próximo pedido
        </div>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="#" style="display: inline-block; background: #90bb13; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
            Volver a la tienda
        </a>
    </div>

    <p style="font-size: 13px; color: #999999; text-align: center; margin-top: 8px;">
        El descuento es válido por tiempo limitado.
    </p>

    <p style="font-size: 12px; color: #999999; text-align: center; margin-top: 32px;">
        Si no deseas recibir estas ofertas, puedes
        <a href="#" style="color: #999999;">darte de baja</a> en cualquier momento.
    </p>

</body>
</html>
