<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hace un año compraste esto</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333333;">

    <div style="text-align: center; margin-bottom: 24px;">
        <div style="background: #b10100; padding: 16px; border-radius: 6px 6px 0 0;">
            <span style="color: white; font-size: 20px; font-weight: bold;">¿Tiempo de renovar?</span>
        </div>
    </div>

    <h2 style="margin: 0 0 12px;">Hola {{ $customer->first_name ?? '' }}</h2>
    <p style="color: #555555; margin-bottom: 20px;">
        Hace exactamente un año realizaste este pedido. ¿Necesitas volver a pedirlo?
    </p>

    @foreach($items as $item)
    <div style="border: 1px solid #e0e0e0; padding: 12px 16px; margin: 8px 0; border-radius: 4px; overflow: hidden;">
        @if (!empty($item->image_url))
        <img src="{{ $item->image_url }}" alt="{{ $item->title }}"
             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; float: left; margin-right: 12px;"
             loading="lazy">
        @endif
        <div style="overflow: hidden;">
            <strong style="display: block; margin-bottom: 4px;">{{ $item->title }}</strong>
            <span style="color: #666666; font-size: 14px;">
                {{ $item->quantity }} unidad(es) &mdash;
                <strong>€{{ number_format((float) $item->price, 2, ',', '.') }}</strong> c/u
            </span>
        </div>
        <div style="clear: both;"></div>
    </div>
    @endforeach

    <div style="text-align: right; font-size: 18px; margin: 20px 0; padding-top: 12px; border-top: 2px solid #e0e0e0;">
        <strong>Total del pedido: €{{ number_format((float) $order->total, 2, ',', '.') }}</strong>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="#" style="display: inline-block; background: #b10100; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
            Volver a pedir
        </a>
    </div>

    <p style="font-size: 12px; color: #999999; text-align: center; margin-top: 32px;">
        Si no deseas recibir estos recordatorios, puedes
        <a href="#" style="color: #999999;">darte de baja</a> en cualquier momento.
    </p>

</body>
</html>
