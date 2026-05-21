<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bajó el precio</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333333;">

    <div style="text-align: center; margin-bottom: 24px;">
        <div style="background: #b10100; padding: 16px; border-radius: 6px 6px 0 0;">
            <span style="color: white; font-size: 20px; font-weight: bold;">¡Bajó el precio!</span>
        </div>
    </div>

    <h2 style="margin: 0 0 12px;">Hola {{ $customer->first_name ?? '' }}</h2>
    <p style="color: #555555; margin-bottom: 24px;">
        El producto que tenías en mente ha bajado de precio. ¡Es el momento de comprarlo!
    </p>

    <div style="border: 2px solid #b10100; border-radius: 6px; padding: 20px; margin-bottom: 24px; text-align: center;">
        <div style="font-size: 14px; color: #999999; margin-bottom: 8px;">
            Precio anterior:
            <span style="text-decoration: line-through;">€{{ number_format((float) $oldPrice, 2, ',', '.') }}</span>
        </div>
        <div style="font-size: 28px; font-weight: bold; color: #b10100;">
            €{{ number_format((float) $newPrice, 2, ',', '.') }}
        </div>
        @if($dropPercent > 0)
        <div style="display: inline-block; background: #b10100; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-top: 8px;">
            -{{ $dropPercent }}% de descuento
        </div>
        @endif
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="#" style="display: inline-block; background: #b10100; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
            Ver producto
        </a>
    </div>

    <p style="font-size: 12px; color: #999999; text-align: center; margin-top: 32px;">
        Si no deseas recibir estas alertas, puedes
        <a href="#" style="color: #999999;">darte de baja</a> en cualquier momento.
    </p>

</body>
</html>
