¡Orden recibida!

Hola {{ $order->customer->name ?? 'Cliente' }},

Hemos recibido tu orden. Aqui estan los detalles:

- Orden: {{ $order->code }}
- Fecha: {{ $order->created_at->format('d/m/Y H:i') }}
- Metodo de pago: {{ $order->payment_method }}
- Estado de pago: {{ $order->payment_status }}

Productos:
@foreach($items as $item)
- {{ $item->product_name }} x{{ $item->qty }} - ${{ number_format($item->total, 2) }}
@endforeach

Subtotal: ${{ number_format($order->sub_total, 2) }}
Envio: ${{ number_format($order->shipping_amount, 2) }}
Total: ${{ number_format($order->total, 2) }}

Gracias por tu compra.
