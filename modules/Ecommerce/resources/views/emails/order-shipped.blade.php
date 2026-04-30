<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden despachada</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; color: #333; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; }
        .header { background: #b10100; padding: 24px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; }
        .content { padding: 24px; }
        .content p { margin-bottom: 12px; line-height: 1.6; }
        .tracking-box { background: #f8fce8; border: 1px solid #c8e87a; border-radius: 6px; padding: 16px; margin: 20px 0; }
        .tracking-box .label { font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 4px; }
        .tracking-box .value { font-size: 18px; font-weight: bold; color: #4a7a00; letter-spacing: 1px; }
        .tracking-box .carrier { font-size: 13px; color: #555; margin-top: 6px; }
        .order-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .order-table th { background: #f8f8f8; padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #666; border-bottom: 2px solid #e0e0e0; }
        .order-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; vertical-align: top; }
        .order-table td.text-right { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #e0e0e0; border-bottom: none; font-size: 15px; }
        .footer { background: #f5f5f5; padding: 16px; text-align: center; font-size: 12px; color: #888; }
        .footer p { margin: 2px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Tu pedido fue despachado</h1>
        </div>

        <div class="content">
            <p>Hola {{ $order->customer->name ?? 'Cliente' }},</p>
            <p>Buenas noticias. Tu orden <strong>#{{ $order->code }}</strong> ha sido despachada y se encuentra en camino.</p>

            @if(isset($shipment) && $shipment->tracking_id)
                <div class="tracking-box">
                    <div class="label">Numero de seguimiento</div>
                    <div class="value">{{ $shipment->tracking_id }}</div>
                    @if($shipment->shipping_company_name)
                        <div class="carrier">Transportista: {{ $shipment->shipping_company_name }}</div>
                    @endif
                    @if($shipment->estimate_date_shipped)
                        <div class="carrier">Entrega estimada: {{ \Carbon\Carbon::parse($shipment->estimate_date_shipped)->format('d/m/Y') }}</div>
                    @endif
                </div>
            @endif

            <table class="order-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th class="text-right">Precio</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->name ?? $item->product->name ?? '—' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td class="text-right">${{ number_format($item->price, 2) }}</td>
                            <td class="text-right">${{ number_format($item->quantity * $item->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3">Total</td>
                        <td class="text-right">${{ number_format($order->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <p>Gracias por tu compra. Si tienes alguna pregunta sobre tu envio, puedes contactarnos respondiendo este correo.</p>
        </div>

        <div class="footer">
            <p>{{ config('app.name') }}</p>
            <p>Este es un correo automatico, por favor no respondas directamente.</p>
        </div>
    </div>
</body>
</html>
