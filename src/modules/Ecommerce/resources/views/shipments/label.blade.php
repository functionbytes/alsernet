<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; width: 283px; }
        .label-header { background: #222; color: #fff; padding: 8px 10px; text-align: center; }
        .label-header h1 { font-size: 14px; letter-spacing: 2px; margin: 0; }
        .label-header p { font-size: 8px; margin: 2px 0 0; opacity: 0.8; }
        .section { padding: 8px 10px; border-bottom: 1px solid #ddd; }
        .section-title { font-size: 7px; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 4px; }
        .big-text { font-size: 13px; font-weight: bold; color: #111; }
        .med-text { font-size: 10px; font-weight: bold; }
        .small-text { font-size: 9px; color: #555; }
        .row-flex { display: table; width: 100%; }
        .col-half { display: table-cell; width: 50%; padding-right: 5px; vertical-align: top; }
        .badge { display: inline-block; background: #eee; border: 1px solid #ccc; padding: 2px 6px; font-size: 9px; border-radius: 3px; }
        .footer { padding: 6px 10px; background: #f5f5f5; text-align: center; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <div class="label-header">
        <h1>ETIQUETA DE ENVIO</h1>
        <p>{{ config('app.name') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Codigo de envio</div>
        <div class="big-text">{{ $shipment->shipment_id ?? ('ENV-' . str_pad($shipment->id, 6, '0', STR_PAD_LEFT)) }}</div>
        @if($shipment->tracking_id)
            <div class="small-text">Tracking: {{ $shipment->tracking_id }}</div>
        @endif
        @if($shipment->shipping_company_name)
            <div class="small-text">Transportista: {{ $shipment->shipping_company_name }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Destinatario</div>
        <div class="med-text">{{ $shipment->order?->customer?->name ?? 'Cliente' }}</div>
        <div class="small-text">{{ $shipment->order?->customer?->email ?? '' }}</div>
        <div class="small-text">Orden: {{ $shipment->order?->code ?? '—' }}</div>
    </div>

    <div class="section">
        <div class="row-flex">
            <div class="col-half">
                <div class="section-title">Peso</div>
                <div class="med-text">{{ $shipment->weight ?? 0 }} kg</div>
            </div>
            <div class="col-half">
                <div class="section-title">Estado</div>
                <div class="badge">{{ strtoupper($shipment->status ?? 'PENDIENTE') }}</div>
            </div>
        </div>
    </div>

    @if($shipment->estimate_date_shipped)
        <div class="section">
            <div class="section-title">Fecha estimada de despacho</div>
            <div class="med-text">{{ \Carbon\Carbon::parse($shipment->estimate_date_shipped)->format('d/m/Y') }}</div>
        </div>
    @endif

    @if($shipment->note)
        <div class="section">
            <div class="section-title">Nota</div>
            <div class="small-text">{{ $shipment->note }}</div>
        </div>
    @endif

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} — {{ config('app.name') }}
    </div>
</body>
</html>
