@php
    $appName = config('app.name', 'Site');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Páginas con content decay</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 720px; margin: 0 auto; padding: 24px;">
    <h1 style="color: #90bb13;">Páginas con content decay</h1>
    <p>
        Se detectaron <strong>{{ $pages->count() }}</strong> páginas en <strong>{{ $appName }}</strong>
        que llevan más de <strong>{{ $daysStale }} días</strong> sin actualizarse y que están
        perdiendo clicks / posición en Search Console.
    </p>

    <table cellspacing="0" cellpadding="8" style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f4f4f4; text-align: left;">
                <th>Página</th>
                <th>Clicks</th>
                <th>Impresiones</th>
                <th>Posición</th>
                <th>Score</th>
                <th>Actualizada</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pages as $page)
                <tr style="border-bottom: 1px solid #eaeaea;">
                    <td>
                        <strong>{{ $page->title ?? 'Sin título' }}</strong><br>
                        <a href="{{ $page->canonical_url }}" style="color: #555;">{{ $page->canonical_url }}</a>
                    </td>
                    <td>{{ (int) ($page->gsc_clicks ?? 0) }}</td>
                    <td>{{ (int) ($page->gsc_impressions ?? 0) }}</td>
                    <td>{{ $page->gsc_position ? number_format($page->gsc_position, 1) : '—' }}</td>
                    <td>{{ $page->seo_score ?? '—' }}</td>
                    <td>{{ optional($page->updated_at)->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 24px; font-size: 13px; color: #666;">
        Recomendación: revisa y actualiza el contenido (datos, enlaces, imágenes),
        o considera un redirect 301 si la página ya no aporta.
    </p>
</body>
</html>
