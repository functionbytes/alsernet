<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu carrito te espera</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333333;">

    <div style="text-align: center; margin-bottom: 24px;">
        <div style="background: #90bb13; padding: 16px; border-radius: 6px 6px 0 0;">
            <span style="color: white; font-size: 20px; font-weight: bold;">Tu carrito te espera</span>
        </div>
    </div>

    <h2 style="margin: 0 0 12px;">Hola {{ $customer->first_name ?? '' }}, ¿olvidaste algo?</h2>
    <p style="color: #555555; margin-bottom: 20px;">
        Notamos que dejaste estos productos en tu carrito. ¡Todavía los tienes reservados!
    </p>

    @foreach($items as $item)
    <div style="border: 1px solid #e0e0e0; padding: 12px 16px; margin: 8px 0; border-radius: 4px; overflow: hidden;">
        <strong style="display: block; margin-bottom: 4px;">{{ $item['name'] ?? 'Producto' }}</strong>
        <span style="color: #666666; font-size: 14px;">
            {{ $item['quantity'] ?? 1 }} unidad(es) &mdash;
            <strong>€{{ number_format((float) ($item['price'] ?? 0), 2, ',', '.') }}</strong> c/u
        </span>
    </div>
    @endforeach

    <div style="text-align: right; font-size: 18px; margin: 20px 0; padding-top: 12px; border-top: 2px solid #e0e0e0;">
        <strong>Total: €{{ number_format((float) $total, 2, ',', '.') }}</strong>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="#" style="display: inline-block; background: #90bb13; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
            Completar mi compra
        </a>
    </div>

    <p style="font-size: 12px; color: #999999; text-align: center; margin-top: 32px;">
        Si no deseas recibir estos recordatorios, puedes
        <a href="#" style="color: #999999;">darte de baja</a> en cualquier momento.
    </p>

</body>
</html>
