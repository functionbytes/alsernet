Pago fallido

Se ha registrado un pago fallido en la tienda.

Motivo: {{ $reason ?: 'No especificado' }}

Detalles:
- Orden: {{ $order->code }}
- Cliente: {{ $order->customer->name ?? 'N/A' }} ({{ $order->customer->email ?? 'N/A' }})
- Monto: ${{ number_format($order->total, 2) }}
- Metodo de pago: {{ $order->payment_method }}
- Fecha: {{ $order->created_at->format('d/m/Y H:i') }}

Notificacion automatica del sistema de pagos.
