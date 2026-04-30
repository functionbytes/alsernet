<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $invoice->code ?? $invoice->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; background: #fff; }
        .page { padding: 40px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 30px; }
        .header-left { display: table-cell; width: 60%; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
        .company-name { font-size: 22px; font-weight: bold; color: #b10100; margin-bottom: 4px; }
        .company-sub { font-size: 11px; color: #666; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #333; letter-spacing: 2px; }
        .invoice-code { font-size: 13px; color: #666; margin-top: 4px; }

        /* Divider */
        .divider { border: none; border-top: 2px solid #b10100; margin: 20px 0; }

        /* Info blocks */
        .info-row { display: table; width: 100%; margin-bottom: 25px; }
        .info-block { display: table-cell; width: 50%; vertical-align: top; }
        .info-block-right { display: table-cell; width: 50%; vertical-align: top; text-align: right; }
        .info-label { font-size: 10px; font-weight: bold; color: #b10100; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .info-value { font-size: 12px; color: #333; line-height: 1.6; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background-color: #b10100; color: #fff; padding: 9px 10px; text-align: left; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        thead th.text-right { text-align: right; }
        tbody tr:nth-child(even) { background-color: #f8f8f8; }
        tbody td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 12px; color: #333; }
        tbody td.text-right { text-align: right; }
        tbody td.text-center { text-align: center; }

        /* Totals */
        .totals { width: 250px; margin-left: auto; }
        .totals-row { display: table; width: 100%; border-bottom: 1px solid #eee; }
        .totals-label { display: table-cell; padding: 5px 10px; font-size: 12px; color: #555; }
        .totals-value { display: table-cell; padding: 5px 10px; font-size: 12px; color: #333; text-align: right; }
        .totals-total { border-top: 2px solid #b10100; margin-top: 4px; }
        .totals-total .totals-label { font-size: 14px; font-weight: bold; color: #333; }
        .totals-total .totals-value { font-size: 14px; font-weight: bold; color: #b10100; }

        /* Status badge */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: bold; }
        .badge-paid { background-color: #d4edda; color: #155724; }
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-other { background-color: #e2e3e5; color: #383d41; }

        /* Footer */
        .footer { margin-top: 40px; padding-top: 15px; border-top: 1px solid #ddd; text-align: center; font-size: 10px; color: #999; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            @if($invoice->company_logo)
                <img src="{{ $invoice->company_logo }}" alt="Logo" style="max-height: 50px; margin-bottom: 8px;"><br>
            @endif
            <div class="company-name">{{ $invoice->company_name ?? config('app.name') }}</div>
            <div class="company-sub">Sistema de facturación</div>
        </div>
        <div class="header-right">
            <div class="invoice-title">FACTURA</div>
            <div class="invoice-code"># {{ $invoice->code ?? $invoice->id }}</div>
            <div style="margin-top: 8px;">
                @if($invoice->status === 'paid')
                    <span class="badge badge-paid">PAGADA</span>
                @elseif($invoice->status === 'pending')
                    <span class="badge badge-pending">PENDIENTE</span>
                @else
                    <span class="badge badge-other">{{ strtoupper($invoice->status ?? '—') }}</span>
                @endif
            </div>
        </div>
    </div>

    <hr class="divider">

    {{-- Client + Invoice info --}}
    <div class="info-row">
        <div class="info-block">
            <div class="info-label">Facturado a</div>
            <div class="info-value">
                <strong>{{ $invoice->customer_name ?? '—' }}</strong><br>
                @if($invoice->customer_email){{ $invoice->customer_email }}<br>@endif
                @if($invoice->customer_phone){{ $invoice->customer_phone }}<br>@endif
                @if($invoice->customer_address){{ $invoice->customer_address }}<br>@endif
                @if($invoice->customer_tax_id)RFC/NIT: {{ $invoice->customer_tax_id }}@endif
            </div>
        </div>
        <div class="info-block-right">
            <div class="info-label">Datos de factura</div>
            <div class="info-value">
                <strong>Fecha emisión:</strong> {{ $invoice->created_at?->format('d/m/Y') ?? '—' }}<br>
                @if($invoice->paid_at)
                    <strong>Fecha pago:</strong> {{ $invoice->paid_at->format('d/m/Y') }}<br>
                @endif
                @if($invoice->shipping_method)
                    <strong>Método envío:</strong> {{ $invoice->shipping_method }}<br>
                @endif
                @if($invoice->coupon_code)
                    <strong>Cupón:</strong> {{ $invoice->coupon_code }}
                @endif
            </div>
        </div>
    </div>

    {{-- Items table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Descripción</th>
                <th class="text-right" style="width: 15%;">Precio unit.</th>
                <th class="text-right" style="width: 15%;">Cantidad</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
                <tr>
                    <td>{{ $item->name ?? '—' }}</td>
                    <td class="text-right">${{ number_format(($item->sub_total && $item->qty ? $item->sub_total / $item->qty : 0), 2) }}</td>
                    <td class="text-right">{{ $item->qty ?? 1 }}</td>
                    <td class="text-right">${{ number_format($item->sub_total ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; color:#999; padding: 20px;">Sin items registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <div class="totals-row">
            <div class="totals-label">Subtotal</div>
            <div class="totals-value">${{ number_format($invoice->sub_total ?? 0, 2) }}</div>
        </div>
        @if(($invoice->tax_amount ?? 0) > 0)
        <div class="totals-row">
            <div class="totals-label">Impuestos</div>
            <div class="totals-value">${{ number_format($invoice->tax_amount, 2) }}</div>
        </div>
        @endif
        @if(($invoice->shipping_amount ?? 0) > 0)
        <div class="totals-row">
            <div class="totals-label">Envío</div>
            <div class="totals-value">${{ number_format($invoice->shipping_amount, 2) }}</div>
        </div>
        @endif
        @if(($invoice->discount_amount ?? 0) > 0)
        <div class="totals-row">
            <div class="totals-label">Descuento</div>
            <div class="totals-value">-${{ number_format($invoice->discount_amount, 2) }}</div>
        </div>
        @endif
        <div class="totals-row totals-total">
            <div class="totals-label">Total</div>
            <div class="totals-value">${{ number_format($invoice->amount ?? 0, 2) }}</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>{{ config('app.name') }} &mdash; Generado el {{ now()->format('d/m/Y H:i') }}</p>
        @if($invoice->description)
            <p style="margin-top: 6px;">{{ $invoice->description }}</p>
        @endif
    </div>

</div>
</body>
</html>
