<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago — Pedido {{ $order->code }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; padding: 0; margin: 0; background: #f8f9fa; }
        .header { background: #fff; padding: 16px 20px; border-bottom: 1px solid #e9ecef; }
        .header h1 { margin: 0; font-size: 18px; color: #333; }
        .header small { color: #888; }
        .container { padding: 24px 20px; max-width: 480px; margin: 0 auto; }
        .summary { background: #fff; border-radius: 12px; padding: 16px 20px; margin-bottom: 16px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .summary-row.total { border-top: 1px solid #e9ecef; padding-top: 12px; margin-top: 4px; font-weight: 600; }
        .cancel-link { display: block; text-align: center; margin-top: 24px; color: #888; font-size: 14px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Completa tu pago</h1>
        <small>Pedido {{ $order->code }}</small>
    </div>

    <div class="container">
        <div class="summary">
            <div class="summary-row"><span>Subtotal</span><strong>${{ number_format($order->sub_total, 0) }}</strong></div>
            @if ($order->shipping_amount > 0)
                <div class="summary-row"><span>Envío</span><strong>${{ number_format($order->shipping_amount, 0) }}</strong></div>
            @endif
            @if ($order->tax_amount > 0)
                <div class="summary-row"><span>Impuestos</span><strong>${{ number_format($order->tax_amount, 0) }}</strong></div>
            @endif
            @if ($order->discount_amount > 0)
                <div class="summary-row"><span>Descuento</span><strong>-${{ number_format($order->discount_amount, 0) }}</strong></div>
            @endif
            <div class="summary-row total"><span>Total</span><strong>${{ number_format($order->total, 0) }}</strong></div>
        </div>

        {!! $widgetHtml !!}

        <a href="{{ $returnUrl }}" class="cancel-link">Cancelar y volver a la app</a>
    </div>
</body>
</html>
