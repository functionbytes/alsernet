<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confirmacion de pago</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #90bb13; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .btn { display: inline-block; padding: 12px 24px; background: #90bb13; color: white; text-decoration: none; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Pago confirmado!</h1>
        </div>
        <div class="content">
            <p>Hola {{ $order->customer->name ?? 'Cliente' }},</p>
            <p>Tu pago ha sido procesado exitosamente. Aqui estan los detalles:</p>

            <table>
                <tr>
                    <th>Orden</th>
                    <td>{{ $order->code }}</td>
                </tr>
                <tr>
                    <th>Referencia de pago</th>
                    <td>{{ $payment->charge_id }}</td>
                </tr>
                <tr>
                    <th>Monto</th>
                    <td>{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                </tr>
                <tr>
                    <th>Metodo</th>
                    <td>{{ $payment->payment_channel }}</td>
                </tr>
                <tr>
                    <th>Fecha</th>
                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            </table>

            <p style="text-align: center;">
                <a href="{{ route('shop.index') }}" class="btn">Ver mi orden</a>
            </p>
        </div>
        <div class="footer">
            <p>Gracias por tu compra.</p>
        </div>
    </div>
</body>
</html>
