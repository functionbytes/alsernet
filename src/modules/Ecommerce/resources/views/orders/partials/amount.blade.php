<table class="table table-borderless table-sm mb-0">
    <tr>
        <td class="text-muted">Subtotal</td>
        <td class="text-end">${{ number_format($order->sub_total, 2) }}</td>
    </tr>
    @if($order->shipping_amount > 0)
    <tr>
        <td class="text-muted">Envio</td>
        <td class="text-end">${{ number_format($order->shipping_amount, 2) }}</td>
    </tr>
    @endif
    @if($order->tax_amount > 0)
    <tr>
        <td class="text-muted">Impuestos</td>
        <td class="text-end">${{ number_format($order->tax_amount, 2) }}</td>
    </tr>
    @endif
    @if($order->discount_amount > 0)
    <tr>
        <td class="text-muted">Descuento</td>
        <td class="text-end text-success">-${{ number_format($order->discount_amount, 2) }}</td>
    </tr>
    @endif
    <tr class="border-top">
        <td class="fw-bold">Total</td>
        <td class="text-end fw-bold">${{ number_format($order->total, 2) }}</td>
    </tr>
</table>
