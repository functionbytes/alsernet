Instrucciones de pago por transferencia bancaria

Hola {{ $order->customer->name ?? 'Cliente' }},

Gracias por tu pedido. Para completar tu pago, realiza una transferencia bancaria con los siguientes datos:

DATOS BANCARIOS
---------------
Banco: {{ $bankDetails['bank_name'] ?? '' }}
Tipo de cuenta: {{ $bankDetails['account_type'] ?? '' }}
Numero de cuenta: {{ $bankDetails['account_number'] ?? '' }}
Titular: {{ $bankDetails['account_holder'] ?? '' }}
Tipo de documento: {{ $bankDetails['document_type'] ?? '' }}
Numero de documento: {{ $bankDetails['document_number'] ?? '' }}

DETALLES DE LA ORDEN
--------------------
Orden: {{ $order->code }}
Total a pagar: ${{ number_format($order->total, 2) }}

@if (!empty($bankDetails['instructions']))
INSTRUCCIONES ADICIONALES
-------------------------
{{ $bankDetails['instructions'] }}

@endif
IMPORTANTE: Usa el codigo de orden {{ $order->code }} como referencia en tu transferencia para que podamos identificar tu pago.

Una vez realizado el pago, tu orden sera procesada y recibiras una confirmacion.

Gracias por tu compra.
