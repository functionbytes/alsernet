<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instrucciones de pago</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #90bb13; color: #fff; padding: 32px 32px 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 8px 0 0; opacity: .85; font-size: 14px; }
        .body { padding: 32px; }
        .greeting { font-size: 16px; color: #1a2030; margin-bottom: 16px; }
        .bank-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .bank-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .bank-table td:first-child { color: #666; width: 45%; }
        .bank-table td:last-child { font-weight: 600; color: #1a2030; }
        .amount { font-size: 24px; font-weight: 700; color: #90bb13; }
        .reference { font-family: monospace; font-size: 16px; background: #f8f9fa; padding: 8px 12px; border-radius: 4px; display: inline-block; }
        .instructions { background: #f0f7e6; border-left: 4px solid #90bb13; padding: 16px; border-radius: 0 4px 4px 0; margin: 20px 0; font-size: 14px; color: #444; }
        .footer { padding: 24px 32px; background: #f8f9fa; text-align: center; font-size: 12px; color: #888; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Instrucciones de pago</h1>
        <p>Orden #{{ $order->code }}</p>
    </div>
    <div class="body">
        <p class="greeting">Hola{{ $order->customer ? ', '.$order->customer->name : '' }},</p>
        <p style="color:#444;font-size:14px;">Tu orden ha sido confirmada. Para completar tu compra, realiza la transferencia bancaria con los siguientes datos:</p>

        <p style="font-size:13px;color:#666;margin-bottom:4px;">Monto a transferir:</p>
        <p class="amount">${{ number_format($order->total, 2) }}</p>

        <table class="bank-table">
            @if($bankDetails['bank_name'])
                <tr><td>Banco</td><td>{{ $bankDetails['bank_name'] }}</td></tr>
            @endif
            @if($bankDetails['account_type'])
                <tr><td>Tipo de cuenta</td><td>{{ $bankDetails['account_type'] }}</td></tr>
            @endif
            @if($bankDetails['account_number'])
                <tr><td>Número de cuenta</td><td>{{ $bankDetails['account_number'] }}</td></tr>
            @endif
            @if($bankDetails['account_holder'])
                <tr><td>Titular</td><td>{{ $bankDetails['account_holder'] }}</td></tr>
            @endif
            @if($bankDetails['document_type'] && $bankDetails['document_number'])
                <tr><td>{{ $bankDetails['document_type'] }}</td><td>{{ $bankDetails['document_number'] }}</td></tr>
            @endif
            <tr>
                <td>Referencia</td>
                <td><span class="reference">{{ $order->code }}</span></td>
            </tr>
        </table>

        @if($bankDetails['instructions'])
            <div class="instructions">{{ $bankDetails['instructions'] }}</div>
        @endif

        <p style="font-size:13px;color:#888;margin-top:20px;">Una vez realizada la transferencia, tu pedido será procesado. Si tienes alguna duda, contáctanos respondiendo este correo.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
    </div>
</div>
</body>
</html>
