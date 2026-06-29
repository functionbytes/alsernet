<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Producto disponible de nuevo</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333333;">

    <div style="text-align: center; margin-bottom: 24px;">
        <div style="background: #90bb13; padding: 16px; border-radius: 6px 6px 0 0;">
            <span style="color: white; font-size: 20px; font-weight: bold;">Vuelve disponible</span>
        </div>
    </div>

    <h2 style="margin: 0 0 12px;">Hola {{ $customer->first_name ?? '' }}</h2>
    <p style="color: #555555; margin-bottom: 24px;">
        ¡Buenas noticias! El producto que esperabas vuelve a estar disponible. No esperes demasiado, el stock puede agotarse pronto.
    </p>

    <div style="border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px; margin-bottom: 24px; text-align: center;">
        @if(!empty($productData['name']))
        <div style="font-size: 18px; font-weight: bold; margin-bottom: 8px;">{{ $productData['name'] }}</div>
        @endif
        @if(!empty($productData['price']))
        <div style="font-size: 22px; color: #90bb13; font-weight: bold;">
            €{{ number_format((float) $productData['price'], 2, ',', '.') }}
        </div>
        @endif
        <div style="display: inline-block; background: #13C672; color: white; padding: 4px 12px; border-radius: 20px; font-size: 13px; margin-top: 8px;">
            Disponible ahora
        </div>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="#" style="display: inline-block; background: #90bb13; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
            Comprar ahora
        </a>
    </div>

    <p style="font-size: 12px; color: #999999; text-align: center; margin-top: 32px;">
        Si no deseas recibir estas alertas, puedes
        <a href="#" style="color: #999999;">darte de baja</a> en cualquier momento.
    </p>

</body>
</html>
