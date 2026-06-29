<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pago fallido</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .alert { background: #f8d7da; color: #842029; padding: 12px; border-radius: 4px; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pago fallido</h1>
        </div>
        <div class="content">
            <p>Hola administrador,</p>
            <p>Se ha registrado un <strong>pago fallido</strong> en la tienda. A continuacion los detalles:</p>

            <div class="alert">
                <strong>Motivo:</strong> {{ $reason ?: 'No especificado' }}
            </div>

            <table>
                <tr>
                    <th>Orden</th>
                    <td>{{ $order->code }}</td>
                </tr>
                <tr>
                    <th>Cliente</th>
                    <td>{{ $order->customer->name ?? 'N/A' }} ({{ $order->customer->email ?? 'N/A' }})</td>
                </tr>
                <tr>
                    <th>Monto</th>
                    <td>${{ number_format($order->total, 2) }}</td>
                </tr>
                <tr>
                    <th>Metodo de pago</th>
                    <td>{{ $order->payment_method }}</td>
                </tr>
                <tr>
                    <th>Fecha</th>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            </table>

            <p>Puedes gestionar esta orden desde el panel administrativo.</p>
        </div>
        <div class="footer">
            <p>Notificacion automatica del sistema de pagos.</p>
        </div>
    </div>
</body>
</html>
