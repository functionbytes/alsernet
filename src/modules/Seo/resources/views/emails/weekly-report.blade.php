<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte SEO semanal</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:30px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:6px; overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#90bb13; padding:24px 32px;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:bold;">
                                Reporte SEO semanal &mdash; {{ config('app.name') }}
                            </h1>
                            <p style="margin:6px 0 0; color:#e8f5cc; font-size:13px;">
                                {{ now()->format('d/m/Y') }}
                            </p>
                        </td>
                    </tr>

                    <!-- Stats summary -->
                    <tr>
                        <td style="padding:28px 32px 8px;">
                            <h2 style="margin:0 0 16px; color:#333333; font-size:16px;">Resumen de la semana</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr>
                                    <td width="50%" style="padding:10px 12px; background-color:#f9f9f9; border:1px solid #e2e2e2; border-radius:4px;">
                                        <p style="margin:0 0 4px; color:#777777; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Total páginas SEO</p>
                                        <p style="margin:0; color:#111111; font-size:22px; font-weight:bold;">{{ number_format($stats['total_metas']) }}</p>
                                    </td>
                                    <td width="4px"></td>
                                    <td width="50%" style="padding:10px 12px; background-color:#f9f9f9; border:1px solid #e2e2e2; border-radius:4px;">
                                        <p style="margin:0 0 4px; color:#777777; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Score promedio</p>
                                        <p style="margin:0; color:#90bb13; font-size:22px; font-weight:bold;">{{ $stats['avg_score'] }}</p>
                                    </td>
                                </tr>
                                <tr><td colspan="3" style="height:6px;"></td></tr>
                                <tr>
                                    <td width="50%" style="padding:10px 12px; background-color:#f9f9f9; border:1px solid #e2e2e2; border-radius:4px;">
                                        <p style="margin:0 0 4px; color:#777777; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Redirecciones activas</p>
                                        <p style="margin:0; color:#111111; font-size:22px; font-weight:bold;">{{ number_format($stats['total_redirects']) }}</p>
                                    </td>
                                    <td width="4px"></td>
                                    <td width="50%" style="padding:10px 12px; background-color:#fff3f3; border:1px solid #ffd0d0; border-radius:4px;">
                                        <p style="margin:0 0 4px; color:#777777; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Errores 404 activos</p>
                                        <p style="margin:0; color:#e53e3e; font-size:22px; font-weight:bold;">{{ number_format($stats['total_404s']) }}</p>
                                    </td>
                                </tr>
                                <tr><td colspan="3" style="height:6px;"></td></tr>
                                <tr>
                                    <td colspan="3" style="padding:10px 12px; background-color:#f9f9f9; border:1px solid #e2e2e2; border-radius:4px;">
                                        <p style="margin:0 0 4px; color:#777777; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Páginas sin SEO nuevas esta semana</p>
                                        <p style="margin:0; color:#111111; font-size:22px; font-weight:bold;">{{ number_format($stats['new_orphans']) }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Score trend -->
                    <tr>
                        <td style="padding:20px 32px 8px;">
                            <h2 style="margin:0 0 12px; color:#333333; font-size:15px;">Tendencia de puntuaciones</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f7e0; border:1px solid #d0e8a0; border-radius:4px;">
                                <tr>
                                    <td width="50%" align="center" style="padding:14px;">
                                        <p style="margin:0 0 4px; color:#555555; font-size:12px; text-transform:uppercase;">Páginas mejorando</p>
                                        <p style="margin:0; color:#2e7d32; font-size:24px; font-weight:bold;">+{{ $stats['improving_count'] }}</p>
                                    </td>
                                    <td width="50%" align="center" style="padding:14px; border-left:1px solid #d0e8a0;">
                                        <p style="margin:0 0 4px; color:#555555; font-size:12px; text-transform:uppercase;">Páginas empeorando</p>
                                        <p style="margin:0; color:#c62828; font-size:24px; font-weight:bold;">-{{ $stats['declining_count'] }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Worst pages -->
                    @if($stats['worst_pages']->isNotEmpty())
                    <tr>
                        <td style="padding:20px 32px 8px;">
                            <h2 style="margin:0 0 12px; color:#333333; font-size:15px;">Top 5 páginas con peor puntuación</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; border:1px solid #e2e2e2; border-radius:4px; overflow:hidden;">
                                <tr style="background-color:#f4f4f4;">
                                    <td style="padding:8px 12px; font-size:11px; font-weight:bold; color:#555555; text-transform:uppercase; border-bottom:1px solid #e2e2e2;">Página</td>
                                    <td style="padding:8px 12px; font-size:11px; font-weight:bold; color:#555555; text-transform:uppercase; border-bottom:1px solid #e2e2e2; text-align:center; width:80px;">Score</td>
                                    <td style="padding:8px 12px; font-size:11px; font-weight:bold; color:#555555; text-transform:uppercase; border-bottom:1px solid #e2e2e2; text-align:center; width:80px;">Editar</td>
                                </tr>
                                @foreach($stats['worst_pages'] as $page)
                                <tr style="{{ $loop->even ? 'background-color:#f5f6f8;' : '' }}">
                                    <td style="padding:8px 12px; font-size:13px; color:#333333; border-bottom:1px solid #eeeeee;">
                                        {{ $page->title ?: ($page->canonical_url ?: 'Meta #'.$page->id) }}
                                    </td>
                                    <td style="padding:8px 12px; text-align:center; border-bottom:1px solid #eeeeee;">
                                        <span style="display:inline-block; background-color:{{ $page->seo_score >= 75 ? '#90bb13' : ($page->seo_score >= 50 ? '#FEC90F' : '#e53e3e') }}; color:#ffffff; border-radius:3px; padding:2px 8px; font-size:12px; font-weight:bold;">
                                            {{ $page->seo_grade ?? '-' }} {{ $page->seo_score }}
                                        </span>
                                    </td>
                                    <td style="padding:8px 12px; text-align:center; border-bottom:1px solid #eeeeee;">
                                        <a href="{{ route('settings.seo.metas.edit', $page->id) }}"
                                           style="color:#90bb13; font-size:12px; text-decoration:none;">Ver</a>
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- Top 404s -->
                    @if($stats['top_404s']->isNotEmpty())
                    <tr>
                        <td style="padding:20px 32px 8px;">
                            <h2 style="margin:0 0 12px; color:#333333; font-size:15px;">Top 5 errores 404 más frecuentes</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; border:1px solid #ffd0d0; border-radius:4px; overflow:hidden;">
                                <tr style="background-color:#fff3f3;">
                                    <td style="padding:8px 12px; font-size:11px; font-weight:bold; color:#555555; text-transform:uppercase; border-bottom:1px solid #ffd0d0;">Ruta</td>
                                    <td style="padding:8px 12px; font-size:11px; font-weight:bold; color:#555555; text-transform:uppercase; border-bottom:1px solid #ffd0d0; text-align:center; width:80px;">Hits</td>
                                </tr>
                                @foreach($stats['top_404s'] as $log)
                                <tr style="{{ $loop->even ? 'background-color:#fffafa;' : '' }}">
                                    <td style="padding:8px 12px; font-size:13px; color:#333333; border-bottom:1px solid #ffe8e8; font-family:monospace;">
                                        {{ $log->path }}
                                    </td>
                                    <td style="padding:8px 12px; text-align:center; border-bottom:1px solid #ffe8e8;">
                                        <span style="color:#e53e3e; font-weight:bold; font-size:13px;">{{ number_format($log->hit_count) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- CTA -->
                    <tr>
                        <td style="padding:28px 32px;">
                            <a href="{{ route('settings.seo.report.index') }}"
                               style="display:inline-block; background-color:#90bb13; color:#ffffff; text-decoration:none; padding:12px 28px; border-radius:4px; font-size:15px; font-weight:bold;">
                                Ver reporte completo
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f4f4f4; padding:16px 32px; border-top:1px solid #e2e2e2;">
                            <p style="margin:0; color:#999999; font-size:12px;">
                                Reporte semanal automático generado por el módulo SEO de {{ config('app.name') }}.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
