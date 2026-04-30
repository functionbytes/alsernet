<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completa tu pedido</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; color: #333; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; }
        .header { background: #b10100; padding: 24px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; }
        .content { padding: 24px; }
        .content p { margin-bottom: 12px; line-height: 1.6; }
        .order-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .order-table th { background: #f8f8f8; padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #666; border-bottom: 2px solid #e0e0e0; }
        .order-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; vertical-align: top; }
        .order-table td.text-right { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #e0e0e0; border-bottom: none; font-size: 15px; }
        .cta-button { display: block; width: fit-content; margin: 24px auto; background: #b10100; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 16px; font-weight: bold; text-align: center; }
        .footer { background: #f5f5f5; padding: 16px; text-align: center; font-size: 12px; color: #888; }
        .footer p { margin: 2px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Tu carrito te está esperando</h1>
        </div>

        <div class="content">
            <p>Hola {{ $order->customer->name ?? 'Cliente' }},</p>
            <p>Notamos que dejaste artículos en tu carrito. ¡Puedes completar tu compra en cualquier momento con el enlace de abajo!</p>

            <table class="order-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->qty }}</td>
                            <td class="text-right">${{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td class="text-right">${{ number_format($order->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <a href="{{ url('/orden/' . $order->token . '/recover') }}" class="cta-button">Completar mi pedido</a>

            <p style="font-size:12px;color:#888;text-align:center;">Este enlace es personal. Si ya completaste tu compra, puedes ignorar este mensaje.</p>
        </div>

        <div class="footer">
            <p>{{ config('app.name') }}</p>
            <p>Este es un correo automatico, por favor no respondas directamente.</p>
        </div>
    </div>
</body>
</html>
