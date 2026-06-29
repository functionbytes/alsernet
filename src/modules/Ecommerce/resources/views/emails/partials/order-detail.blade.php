<table style="width:100%;border-collapse:collapse;margin:20px 0;">
    <thead>
        <tr>
            <th style="background:#f8f8f8;padding:10px 12px;text-align:left;font-size:12px;text-transform:uppercase;color:#666;border-bottom:2px solid #e0e0e0;">Producto</th>
            <th style="background:#f8f8f8;padding:10px 12px;text-align:center;font-size:12px;text-transform:uppercase;color:#666;border-bottom:2px solid #e0e0e0;">Cant.</th>
            <th style="background:#f8f8f8;padding:10px 12px;text-align:right;font-size:12px;text-transform:uppercase;color:#666;border-bottom:2px solid #e0e0e0;">Precio</th>
            <th style="background:#f8f8f8;padding:10px 12px;text-align:right;font-size:12px;text-transform:uppercase;color:#666;border-bottom:2px solid #e0e0e0;">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
            <tr>
                <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:14px;vertical-align:top;">
                    {{ $item->product_name ?? $item->name ?? '—' }}
                </td>
                <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:14px;text-align:center;">
                    {{ $item->qty ?? $item->quantity }}
                </td>
                <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:14px;text-align:right;">
                    ${{ number_format($item->price, 2) }}
                </td>
                <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:14px;text-align:right;">
                    ${{ number_format(($item->qty ?? $item->quantity) * $item->price, 2) }}
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="padding:8px 12px;text-align:right;font-size:13px;color:#555;">Subtotal</td>
            <td style="padding:8px 12px;text-align:right;font-size:13px;">${{ number_format($order->sub_total, 2) }}</td>
        </tr>
        @if($order->shipping_amount > 0)
            <tr>
                <td colspan="3" style="padding:8px 12px;text-align:right;font-size:13px;color:#555;">Envio</td>
                <td style="padding:8px 12px;text-align:right;font-size:13px;">${{ number_format($order->shipping_amount, 2) }}</td>
            </tr>
        @endif
        @if(isset($order->tax_amount) && $order->tax_amount > 0)
            <tr>
                <td colspan="3" style="padding:8px 12px;text-align:right;font-size:13px;color:#555;">Impuestos</td>
                <td style="padding:8px 12px;text-align:right;font-size:13px;">${{ number_format($order->tax_amount, 2) }}</td>
            </tr>
        @endif
        @if(isset($order->discount_amount) && $order->discount_amount > 0)
            <tr>
                <td colspan="3" style="padding:8px 12px;text-align:right;font-size:13px;color:#555;">Descuento</td>
                <td style="padding:8px 12px;text-align:right;font-size:13px;color:#c0392b;">-${{ number_format($order->discount_amount, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td colspan="3" style="padding:10px 12px;text-align:right;font-weight:bold;font-size:15px;border-top:2px solid #e0e0e0;">Total</td>
            <td style="padding:10px 12px;text-align:right;font-weight:bold;font-size:15px;border-top:2px solid #e0e0e0;color:#90bb13;">
                ${{ number_format($order->total, 2) }}
            </td>
        </tr>
    </tfoot>
</table>
