<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos que te pueden interesar</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333333;">

    <div style="text-align: center; margin-bottom: 24px;">
        <div style="background: #b10100; padding: 16px; border-radius: 6px 6px 0 0;">
            <span style="color: white; font-size: 20px; font-weight: bold;">Productos para ti</span>
        </div>
    </div>

    <h2 style="margin: 0 0 12px;">Hola {{ $customer->first_name ?? '' }}</h2>
    <p style="color: #555555; margin-bottom: 24px;">
        Basándonos en tu compra reciente, creemos que estos productos también te pueden interesar.
    </p>

    <div style="display: grid; gap: 16px;">
        @foreach($recommendations as $product)
        <div style="border: 1px solid #e0e0e0; border-radius: 6px; padding: 16px; overflow: hidden;">
            @if (!empty($product->image_url))
            <img src="{{ $product->image_url }}" alt="{{ $product->title }}"
                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; float: left; margin-right: 16px;"
                 loading="lazy">
            @endif
            <div style="overflow: hidden;">
                <strong style="display: block; margin-bottom: 4px; font-size: 15px;">
                    {{ $product->title }}
                </strong>
                @if (!empty($product->price))
                <span style="color: #b10100; font-size: 16px; font-weight: bold;">
                    €{{ number_format((float) $product->price, 2, ',', '.') }}
                </span>
                @endif
                @if (!empty($product->url))
                <div style="margin-top: 8px;">
                    <a href="{{ $product->url }}"
                       style="display: inline-block; background: #b10100; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold;">
                        Ver producto
                    </a>
                </div>
                @endif
            </div>
            <div style="clear: both;"></div>
        </div>
        @endforeach
    </div>

    <p style="font-size: 12px; color: #999999; text-align: center; margin-top: 32px;">
        Si no deseas recibir estas recomendaciones, puedes
        <a href="#" style="color: #999999;">darte de baja</a> en cualquier momento.
    </p>

</body>
</html>
