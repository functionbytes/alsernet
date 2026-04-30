<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte mensual de reseñas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .page { page-break-after: always; padding: 40px 40px 60px; min-height: 100vh; position: relative; }
        .page:last-child { page-break-after: auto; }
        .footer { position: absolute; bottom: 20px; left: 40px; right: 40px; border-top: 1px solid #e0e0e0; padding-top: 8px; font-size: 10px; color: #aaa; display: table; width: calc(100% - 80px); }
        .footer-left { display: table-cell; text-align: left; }
        .footer-right { display: table-cell; text-align: right; }

        /* Cover */
        .cover-header { background-color: #b10100; padding: 50px 40px; text-align: center; border-radius: 4px; margin-bottom: 40px; }
        .cover-header h1 { color: #fff; font-size: 28px; margin-bottom: 10px; }
        .cover-header p { color: rgba(255,255,255,0.85); font-size: 16px; }
        .cover-meta { text-align: center; margin-top: 30px; }
        .cover-meta p { font-size: 14px; color: #555; margin-bottom: 6px; }

        /* Section title */
        .section-title { font-size: 16px; font-weight: bold; color: #333; border-bottom: 2px solid #b10100; padding-bottom: 8px; margin-bottom: 20px; }

        /* KPI grid */
        .kpi-grid { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .kpi-cell { width: 50%; padding: 6px; vertical-align: top; }
        .kpi-box { background: #f8faf0; border: 1px solid #d9e8a0; border-radius: 6px; padding: 20px; text-align: center; }
        .kpi-value { font-size: 32px; font-weight: 700; color: #b10100; }
        .kpi-label { font-size: 11px; color: #888; margin-top: 6px; }

        /* Bar chart */
        .bar-row { margin-bottom: 10px; }
        .bar-label { display: inline-block; width: 80px; font-size: 12px; color: #555; }
        .bar-track { display: inline-block; width: 300px; background: #f0f0f0; border-radius: 3px; height: 16px; vertical-align: middle; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 3px; background: #b10100; }
        .bar-count { display: inline-block; margin-left: 8px; font-size: 11px; color: #888; }

        /* Review card */
        .review-card { border-left: 3px solid #b10100; background: #f8faf0; padding: 12px 16px; margin-bottom: 12px; border-radius: 0 4px 4px 0; }
        .review-card.negative { border-left-color: #FA896B; background: #fff9f8; }
        .review-stars { font-size: 14px; margin-bottom: 4px; }
        .review-meta { font-size: 11px; color: #888; margin-bottom: 6px; }
        .review-text { font-style: italic; color: #555; font-size: 12px; }

        /* Response performance */
        .perf-table { width: 100%; border-collapse: collapse; }
        .perf-table td { padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .perf-table .label { color: #888; width: 220px; }
        .perf-table .value { font-weight: 600; color: #333; }
        .perf-table .value.good { color: #13C672; }
        .perf-table .value.warn { color: #FA896B; }
    </style>
</head>
<body>

{{-- Page 1: Cover --}}
<div class="page">
    <div class="cover-header">
        <h1>Reporte mensual de reseñas</h1>
        <p>{{ $period }}</p>
    </div>
    <div class="cover-meta">
        @if (!empty($locationName))
            <p><strong>Ubicacion:</strong> {{ $locationName }}</p>
        @endif
        <p><strong>Periodo:</strong> {{ $periodFrom }} — {{ $periodTo }}</p>
        <p><strong>Total de reseñas analizadas:</strong> {{ $summary['total_reviews'] }}</p>
    </div>
    <div class="footer">
        <span class="footer-left">Generado el {{ $generatedAt }}</span>
        <span class="footer-right">Alsernet — Gestion de reseñas</span>
    </div>
</div>

{{-- Page 2: Executive summary / KPIs --}}
<div class="page">
    <div class="section-title">Resumen ejecutivo</div>

    <table class="kpi-grid">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-box">
                    <div class="kpi-value">{{ $summary['total_reviews'] }}</div>
                    <div class="kpi-label">Total de reseñas</div>
                </div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-box">
                    <div class="kpi-value">{{ number_format($summary['avg_rating'], 1) }}</div>
                    <div class="kpi-label">Rating promedio</div>
                </div>
            </td>
        </tr>
        <tr>
            <td class="kpi-cell">
                <div class="kpi-box">
                    <div class="kpi-value">{{ number_format($summary['response_rate'], 1) }}%</div>
                    <div class="kpi-label">Tasa de respuesta</div>
                </div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-box">
                    <div class="kpi-value">{{ $summary['pending_replies'] }}</div>
                    <div class="kpi-label">Reseñas sin respuesta</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <span class="footer-left">Generado el {{ $generatedAt }}</span>
        <span class="footer-right">Alsernet — Gestion de reseñas</span>
    </div>
</div>

{{-- Page 3: Rating distribution --}}
<div class="page">
    <div class="section-title">Distribucion de calificaciones</div>

    @php
        $maxCount = max(array_values($ratingDistribution) ?: [1]);
    @endphp

    @foreach ([5, 4, 3, 2, 1] as $star)
        @php
            $count = $ratingDistribution[$star] ?? 0;
            $width = $maxCount > 0 ? round(($count / $maxCount) * 100) : 0;
            $label = str_repeat('★', $star) . str_repeat('☆', 5 - $star);
        @endphp
        <div class="bar-row">
            <span class="bar-label">{{ $star }} estrella{{ $star !== 1 ? 's' : '' }}</span>
            <span class="bar-track">
                <span class="bar-fill" style="width: {{ $width }}%;"></span>
            </span>
            <span class="bar-count">{{ $count }}</span>
        </div>
    @endforeach

    <div class="footer">
        <span class="footer-left">Generado el {{ $generatedAt }}</span>
        <span class="footer-right">Alsernet — Gestion de reseñas</span>
    </div>
</div>

{{-- Page 4: Top 5 positive + top 5 needing attention --}}
<div class="page">
    <div class="section-title">Mejores reseñas del mes</div>

    @forelse ($topPositive as $review)
        <div class="review-card">
            <div class="review-stars">
                @for ($i = 1; $i <= 5; $i++)
                    <span style="color: {{ $i <= ($review['rating'] ?? 0) ? '#FEC90F' : '#ddd' }};">★</span>
                @endfor
            </div>
            <div class="review-meta">{{ $review['reviewer_name'] ?? 'Anonimo' }} · {{ $review['location_name'] ?? '' }} · {{ $review['review_date'] ?? '' }}</div>
            @if (!empty($review['comment']))
                <div class="review-text">"{{ $review['comment'] }}"</div>
            @endif
        </div>
    @empty
        <p style="color: #888;">Sin reseñas positivas en este periodo.</p>
    @endforelse

    <div style="margin-top: 24px;">
        <div class="section-title">Reseñas que necesitan atencion</div>

        @forelse ($topNegative as $review)
            <div class="review-card negative">
                <div class="review-stars">
                    @for ($i = 1; $i <= 5; $i++)
                        <span style="color: {{ $i <= ($review['rating'] ?? 0) ? '#FEC90F' : '#ddd' }};">★</span>
                    @endfor
                </div>
                <div class="review-meta">{{ $review['reviewer_name'] ?? 'Anonimo' }} · {{ $review['location_name'] ?? '' }} · {{ $review['review_date'] ?? '' }}</div>
                @if (!empty($review['comment']))
                    <div class="review-text">"{{ $review['comment'] }}"</div>
                @endif
            </div>
        @empty
            <p style="color: #888;">Sin reseñas negativas pendientes.</p>
        @endforelse
    </div>

    <div class="footer">
        <span class="footer-left">Generado el {{ $generatedAt }}</span>
        <span class="footer-right">Alsernet — Gestion de reseñas</span>
    </div>
</div>

{{-- Page 5: Response performance --}}
<div class="page">
    <div class="section-title">Rendimiento de respuestas</div>

    <table class="perf-table">
        <tr>
            <td class="label">Total de reseñas con comentario</td>
            <td class="value">{{ $responsePerformance['with_comment'] }}</td>
        </tr>
        <tr>
            <td class="label">Reseñas respondidas</td>
            <td class="value">{{ $responsePerformance['replied'] }}</td>
        </tr>
        <tr>
            <td class="label">Tasa de respuesta</td>
            <td class="value {{ $responsePerformance['response_rate'] >= 80 ? 'good' : 'warn' }}">
                {{ number_format($responsePerformance['response_rate'], 1) }}%
            </td>
        </tr>
        <tr>
            <td class="label">Tiempo promedio de respuesta</td>
            <td class="value">{{ $responsePerformance['avg_response_time_formatted'] }}</td>
        </tr>
        <tr>
            <td class="label">Reseñas sin respuesta</td>
            <td class="value {{ $responsePerformance['without_reply'] > 0 ? 'warn' : 'good' }}">
                {{ $responsePerformance['without_reply'] }}
            </td>
        </tr>
    </table>

    <div class="footer">
        <span class="footer-left">Generado el {{ $generatedAt }}</span>
        <span class="footer-right">Alsernet — Gestion de reseñas</span>
    </div>
</div>

</body>
</html>
