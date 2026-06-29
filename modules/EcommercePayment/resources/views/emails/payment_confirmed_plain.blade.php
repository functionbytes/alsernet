¡Pago confirmado!

Hola {{ $order->customer->name ?? 'Cliente' }},

Tu pago ha sido procesado exitosamente.

Detalles:
- Orden: {{ $order->code }}
- Referencia de pago: {{ $payment->charge_id }}
- Monto: {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
- Metodo: {{ $payment->payment_channel }}
- Fecha: {{ $payment->created_at->format('d/m/Y H:i') }}

Gracias por tu compra.
