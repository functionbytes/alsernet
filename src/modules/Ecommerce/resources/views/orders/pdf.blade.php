<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; padding: 30px; }

        /* Header */
        .header { border-bottom: 2px solid #222; padding-bottom: 16px; margin-bottom: 20px; }
        .header-title { font-size: 20px; font-weight: bold; color: #111; letter-spacing: 1px; }
        .header-sub { font-size: 11px; color: #666; margin-top: 4px; }

        /* Sections */
        .section { margin-bottom: 20px; }
        .section-title { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 6px; border-bottom: 1px solid #eee; padding-bottom: 3px; }

        /* Two-column layout using table (DomPDF-safe) */
        .row-table { display: table; width: 100%; margin-bottom: 20px; }
        .col-half { display: table-cell; width: 50%; vertical-align: top; padding-right: 15px; }
        .col-half:last-child { padding-right: 0; }

        /* Item table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #f0f0f0; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #555; padding: 7px 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .items-table td { padding: 8px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .items-table .text-right { text-align: right; }
        .items-table tfoot td { font-weight: bold; border-top: 1px solid #ddd; }

        /* Totals */
        .totals { display: table; width: 260px; margin-left: auto; }
        .total-row { display: table-row; }
        .total-label { display: table-cell; padding: 4px 8px; color: #555; }
        .total-value { display: table-cell; padding: 4px 8px; text-align: right; }
        .total-grand .total-label,
        .total-grand .total-value { font-size: 13px; font-weight: bold; color: #111; border-top: 2px solid #333; padding-top: 8px; }

        /* Footer */
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 9px; color: #999; text-align: center; }

        /* Text helpers */
        .fw-bold { font-weight: bold; }
        .text-muted { color: #777; }
        .text-small { font-size: 10px; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div class="header-title">ORDEN #{{ $order->code }}</div>
        <div class="header-sub">
            {{ config('app.name') }} &nbsp;|&nbsp; Fecha: {{ $order->created_at->format('d/m/Y H:i') }}
            &nbsp;|&nbsp; Estado: {{ strtoupper($order->status instanceof \BackedEnum ? $order->status->value : ($order->status ?? '')) }}
        </div>
    </div>

    {{-- Cliente + Envio --}}
    <div class="row-table">
        <div class="col-half">
            <div class="section-title">Datos del cliente</div>
            <div class="fw-bold">{{ $order->customer?->name ?? 'Cliente' }}</div>
            <div class="text-small text-muted">{{ $order->customer?->email ?? '' }}</div>
            @if($order->customer?->phone)
                <div class="text-small text-muted">{{ $order->customer->phone }}</div>
            @endif
        </div>

        <div class="col-half">
            <div class="section-title">Informacion de envio</div>
            @if($order->shipments->isNotEmpty())
                @php $shipment = $order->shipments->first(); @endphp
                @if($shipment->shipping_company_name)
                    <div class="text-small"><span class="fw-bold">Transportista:</span> {{ $shipment->shipping_company_name }}</div>
                @endif
                @if($shipment->shipment_id)
                    <div class="text-small"><span class="fw-bold">Envio #:</span> {{ $shipment->shipment_id }}</div>
                @endif
                @if($shipment->tracking_id)
                    <div class="text-small"><span class="fw-bold">Tracking:</span> {{ $shipment->tracking_id }}</div>
                @endif
                @if($shipment->estimate_date_shipped)
                    <div class="text-small"><span class="fw-bold">Fecha estimada:</span> {{ \Carbon\Carbon::parse($shipment->estimate_date_shipped)->format('d/m/Y') }}</div>
                @endif
            @else
                <div class="text-small text-muted">Sin envio registrado</div>
            @endif
            @if($order->shipping_method)
                <div class="text-small text-muted">Metodo: {{ $order->shipping_method }}</div>
            @endif
        </div>
    </div>

    {{-- Productos --}}
    <div class="section">
        <div class="section-title">Productos</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-right">Precio unit.</th>
                    <th class="text-right">Cant.</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td class="text-right">{{ number_format($item->price, 2) }}</td>
                        <td class="text-right">{{ $item->qty }}</td>
                        <td class="text-right">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Resumen de totales --}}
    <div class="totals">
        <div class="total-row">
            <div class="total-label">Subtotal</div>
            <div class="total-value">{{ number_format($order->sub_total, 2) }}</div>
        </div>

        @if($order->discount_amount > 0)
            <div class="total-row">
                <div class="total-label">
                    Descuento
                    @if($order->coupon_code)
                        <span class="text-muted">({{ $order->coupon_code }})</span>
                    @endif
                </div>
                <div class="total-value">- {{ number_format($order->discount_amount, 2) }}</div>
            </div>
        @endif

        @if($order->tax_amount > 0)
            <div class="total-row">
                <div class="total-label">Impuestos</div>
                <div class="total-value">{{ number_format($order->tax_amount, 2) }}</div>
            </div>
        @endif

        @if($order->shipping_amount > 0)
            <div class="total-row">
                <div class="total-label">Envio</div>
                <div class="total-value">{{ number_format($order->shipping_amount, 2) }}</div>
            </div>
        @endif

        <div class="total-row total-grand">
            <div class="total-label">TOTAL</div>
            <div class="total-value">{{ number_format($order->total, 2) }}</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} &mdash; {{ config('app.name') }}
    </div>

</body>
</html>
