<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de rendimiento — Remarketing</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        h1 { font-size: 18px; color: #b10100; margin: 0 0 4px; }
        .subtitle { color: #666; font-size: 10px; margin-bottom: 20px; }
        h2 { font-size: 13px; color: #444; border-bottom: 2px solid #b10100; padding-bottom: 4px; margin: 20px 0 10px; }
        .kpi-grid { display: table; width: 100%; margin-bottom: 20px; }
        .kpi-row { display: table-row; }
        .kpi-cell { display: table-cell; width: 14.28%; padding: 8px 6px; background: #f8f8f8; border: 1px solid #e5e5e5; text-align: center; }
        .kpi-value { font-size: 16px; font-weight: bold; color: #b10100; display: block; margin-bottom: 2px; }
        .kpi-label { font-size: 9px; color: #777; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #b10100; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        .text-right { text-align: right; }
        .footer { margin-top: 24px; text-align: right; font-size: 9px; color: #aaa; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Reporte de rendimiento — Remarketing</h1>
    <div class="subtitle">
        Período: {{ $since30d->format('d/m/Y') }} — {{ $generatedAt->format('d/m/Y') }}
        &nbsp;&middot;&nbsp; Generado: {{ $generatedAt->format('d/m/Y H:i') }}
    </div>

    <h2>KPIs últimos 30 días</h2>
    <div class="kpi-grid">
        <div class="kpi-row">
            <div class="kpi-cell">
                <span class="kpi-value">{{ number_format($stats['delivery_rate'], 1) }}%</span>
                <span class="kpi-label">Tasa entrega</span>
            </div>
            <div class="kpi-cell">
                <span class="kpi-value">{{ number_format($stats['open_rate'], 1) }}%</span>
                <span class="kpi-label">Open rate</span>
            </div>
            <div class="kpi-cell">
                <span class="kpi-value">{{ number_format($stats['ctr'], 1) }}%</span>
                <span class="kpi-label">CTR</span>
            </div>
            <div class="kpi-cell">
                <span class="kpi-value">{{ number_format($stats['bounce_rate'], 1) }}%</span>
                <span class="kpi-label">Bounce rate</span>
            </div>
            <div class="kpi-cell">
                <span class="kpi-value">{{ number_format($stats['complaint_rate'], 2) }}%</span>
                <span class="kpi-label">Tasa quejas</span>
            </div>
            <div class="kpi-cell">
                <span class="kpi-value">{{ number_format($stats['unsubscribe_rate'], 2) }}%</span>
                <span class="kpi-label">Tasa baja</span>
            </div>
            <div class="kpi-cell">
                <span class="kpi-value">{{ number_format($stats['revenue_per_recipient'], 2) }} €</span>
                <span class="kpi-label">Revenue/destinatario</span>
            </div>
        </div>
    </div>

    <h2>Métricas por campaña (últimas 50)</h2>
    @if($campaigns->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Campaña</th>
                    <th class="text-right">Enviados</th>
                    <th class="text-right">Open rate</th>
                    <th class="text-right">CTR</th>
                    <th class="text-right">Bounce rate</th>
                    <th class="text-right">Revenue</th>
                    <th>Completado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $c)
                    <tr>
                        <td>{{ $c['name'] }}</td>
                        <td class="text-right">{{ number_format($c['sent']) }}</td>
                        <td class="text-right">{{ $c['open_rate'] }}%</td>
                        <td class="text-right">{{ $c['ctr'] }}%</td>
                        <td class="text-right">{{ $c['bounce_rate'] }}%</td>
                        <td class="text-right">{{ number_format($c['revenue'], 2) }} €</td>
                        <td>{{ $c['completed_at'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color:#888;font-style:italic">No hay campañas completadas.</p>
    @endif

    <div class="footer">Alsernet — Módulo Remarketing</div>
</body>
</html>
