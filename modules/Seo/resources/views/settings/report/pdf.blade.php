<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte SEO - {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; background: #fff; }
        .page { padding: 20px; }

        /* Header */
        .header { border-bottom: 3px solid #b10100; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #2c3e50; font-weight: bold; }
        .header .meta { font-size: 10px; color: #666; margin-top: 4px; }

        /* Summary stats */
        .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .stats-table th { background: #2c3e50; color: #fff; padding: 8px 12px; text-align: left; font-size: 11px; }
        .stats-table td { padding: 7px 12px; border-bottom: 1px solid #eee; font-size: 11px; }
        .stats-table tr:nth-child(even) td { background: #f9f9f9; }

        /* Grade distribution */
        .grade-row { display: inline-block; padding: 3px 8px; border-radius: 3px; color: #fff; font-weight: bold; font-size: 10px; }
        .grade-a { background: #13C672; }
        .grade-b { background: #b10100; }
        .grade-c { background: #FEC90F; color: #333; }
        .grade-d { background: #FA896B; }
        .grade-f { background: #dc3545; }

        /* Main data table */
        .section-title { font-size: 14px; font-weight: bold; color: #2c3e50; margin: 16px 0 8px; border-left: 4px solid #b10100; padding-left: 8px; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
        .data-table thead th { background: #2c3e50; color: #fff; padding: 6px 8px; text-align: left; }
        .data-table tbody td { padding: 5px 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        .data-table tbody tr:nth-child(even) td { background: #f9f9f9; }

        /* Row color coding */
        .row-a td { border-left: 3px solid #13C672; }
        .row-b td:first-child { border-left: 3px solid #b10100; }
        .row-c td:first-child { border-left: 3px solid #FEC90F; }
        .row-d td:first-child { border-left: 3px solid #FA896B; }
        .row-f td:first-child { border-left: 3px solid #dc3545; }
        .row-a { background: #f0fff8 !important; }
        .row-b { background: #f8fbf0 !important; }
        .row-c { background: #fffdf0 !important; }
        .row-d { background: #fff6f4 !important; }
        .row-f { background: #fff0f0 !important; }

        /* Score badge */
        .score-badge { display: inline-block; padding: 2px 6px; border-radius: 3px; color: #fff; font-weight: bold; font-size: 9px; }

        /* Footer */
        .footer { border-top: 1px solid #ddd; margin-top: 20px; padding-top: 8px; font-size: 9px; color: #999; text-align: center; }
        .truncate { overflow: hidden; white-space: nowrap; max-width: 200px; display: inline-block; text-overflow: ellipsis; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <h1>Reporte SEO &mdash; {{ config('app.name') }}</h1>
        <div class="meta">Generado: {{ $generatedAt }} &nbsp;|&nbsp; Total de páginas: {{ $metas->count() }}</div>
    </div>

    {{-- Summary stats --}}
    <table class="stats-table">
        <thead>
            <tr>
                <th>Métrica</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total metas SEO</td>
                <td>{{ number_format($stats['total_metas']) }}</td>
            </tr>
            <tr>
                <td>Score promedio</td>
                <td>{{ $stats['avg_score'] > 0 ? $stats['avg_score'] : 'Sin datos' }}</td>
            </tr>
            <tr>
                <td>Total redirecciones</td>
                <td>{{ number_format($stats['total_redirects']) }}</td>
            </tr>
            @foreach(['A' => '#13C672', 'B' => '#b10100', 'C' => '#FEC90F', 'D' => '#FA896B', 'F' => '#dc3545'] as $grade => $color)
                @if(isset($stats['grade_distribution'][$grade]))
                    <tr>
                        <td>Grado {{ $grade }}</td>
                        <td>{{ $stats['grade_distribution'][$grade] }} páginas</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    {{-- Pages table --}}
    <div class="section-title">Detalle de páginas (top {{ $metas->count() }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30%">Título</th>
                <th style="width:8%">Score</th>
                <th style="width:6%">Grado</th>
                <th style="width:10%">Robots</th>
                <th style="width:28%">Canónica</th>
                <th style="width:18%">Auditado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($metas as $meta)
                @php
                $grade = $meta->seo_grade ?? '';
                $rowClass = match($grade) {
                    'A' => 'row-a',
                    'B' => 'row-b',
                    'C' => 'row-c',
                    'D' => 'row-d',
                    'F' => 'row-f',
                    default => '',
                };
                $scoreColor = match(true) {
                    ($meta->seo_score ?? 0) >= 90 => '#13C672',
                    ($meta->seo_score ?? 0) >= 75 => '#b10100',
                    ($meta->seo_score ?? 0) >= 60 => '#FEC90F',
                    ($meta->seo_score ?? 0) >= 40 => '#FA896B',
                    default => '#dc3545',
                };
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>{{ \Illuminate\Support\Str::limit($meta->title ?? 'Sin título', 60) }}</td>
                    <td>
                        @if($meta->seo_score !== null)
                            <span class="score-badge" style="background:{{ $scoreColor }};">{{ $meta->seo_score }}</span>
                        @else
                            <span style="color:#999;">-</span>
                        @endif
                    </td>
                    <td>{{ $grade ?: '-' }}</td>
                    <td>{{ $meta->robots ?? 'index,follow' }}</td>
                    <td style="word-break:break-all;font-size:8.5px;">{{ \Illuminate\Support\Str::limit($meta->canonical_url ?? '', 50) }}</td>
                    <td>{{ $meta->seo_audited_at?->format('d/m/Y H:i') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        Reporte generado por {{ config('app.name') }} &mdash; {{ $generatedAt }}
    </div>

</div>
</body>
</html>
