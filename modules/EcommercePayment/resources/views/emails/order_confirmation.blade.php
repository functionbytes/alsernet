<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orden recibida</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #b10100; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .btn { display: inline-block; padding: 12px 24px; background: #b10100; color: white; text-decoration: none; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .total { font-size: 1.2rem; font-weight: bold; text-align: right; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Orden recibida!</h1>
        </div>
        <div class="content">
            <p>Hola {{ $order->customer->name ?? 'Cliente' }},</p>
            <p>Hemos recibido tu orden. Aqui estan los detalles:</p>

            <table>
                <tr>
                    <th>Orden</th>
                    <td>{{ $order->code }}</td>
                </tr>
                <tr>
                    <th>Fecha</th>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <th>Metodo de pago</th>
                    <td>{{ $order->payment_method }}</td>
                </tr>
                <tr>
                    <th>Estado de pago</th>
                    <td>{{ $order->payment_status }}</td>
                </tr>
            </table>

            <h3>Productos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->qty }}</td>
                            <td>${{ number_format($item->price, 2) }}</td>
                            <td>${{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total">
                Subtotal: ${{ number_format($order->sub_total, 2) }}<br>
                Envio: ${{ number_format($order->shipping_amount, 2) }}<br>
                Total: ${{ number_format($order->total, 2) }}
            </div>

            <p style="text-align: center; margin-top: 20px;">
                <a href="{{ route('shop.index') }}" class="btn">Ver tienda</a>
            </p>
        </div>
        <div class="footer">
            <p>Gracias por tu compra.</p>
        </div>
    </div>
</body>
</html>
