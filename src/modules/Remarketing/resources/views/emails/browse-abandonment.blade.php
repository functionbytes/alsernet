<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¿Te interesó este producto?</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333333;">

    <div style="text-align: center; margin-bottom: 24px;">
        <div style="background: #90bb13; padding: 16px; border-radius: 6px 6px 0 0;">
            <span style="color: white; font-size: 20px; font-weight: bold;">¿Te interesó este producto?</span>
        </div>
    </div>

    <h2 style="margin: 0 0 12px;">Hola {{ $customer->first_name ?? '' }}</h2>
    <p style="color: #555555; margin-bottom: 20px;">
        Notamos que estuviste viendo este producto. Todavía está disponible por si quieres echarle un vistazo.
    </p>

    <div style="border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px; margin-bottom: 24px; overflow: hidden;">
        @if (!empty($product['product_image_url']) || !empty($product['image_url']))
        <div style="text-align: center; margin-bottom: 16px;">
            <img src="{{ $product['product_image_url'] ?? $product['image_url'] ?? '' }}"
                 alt="{{ $product['product_title'] ?? $product['title'] ?? 'Producto' }}"
                 style="max-width: 200px; max-height: 200px; border-radius: 4px;"
                 loading="lazy">
        </div>
        @endif

        <strong style="display: block; font-size: 16px; margin-bottom: 8px;">
            {{ $product['product_title'] ?? $product['title'] ?? 'Producto' }}
        </strong>

        @if (!empty($product['price']))
        <span style="color: #90bb13; font-size: 18px; font-weight: bold;">
            €{{ number_format((float) $product['price'], 2, ',', '.') }}
        </span>
        @endif
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $product['product_url'] ?? $product['url'] ?? '#' }}"
           style="display: inline-block; background: #90bb13; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
            Ver producto
        </a>
    </div>

    <p style="font-size: 12px; color: #999999; text-align: center; margin-top: 32px;">
        Si no deseas recibir estos recordatorios, puedes
        <a href="#" style="color: #999999;">darte de baja</a> en cualquier momento.
    </p>

</body>
</html>
