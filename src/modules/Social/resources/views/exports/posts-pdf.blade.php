<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Publicaciones</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { color: #333; border-bottom: 2px solid #5D87FF; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #5D87FF; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .header { margin-bottom: 20px; }
        .meta { color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Publicaciones</h1>
        <p class="meta">Generado el: {{ $generated_at }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Red Social</th>
                <th>Contenido</th>
                <th>Estado</th>
                <th>Engagement</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($posts as $post)
                <tr>
                    <td>{{ $post->id }}</td>
                    <td>{{ $post->socialAccount?->name ?? 'N/A' }}</td>
                    <td>{{ Str::limit($post->content, 80) }}</td>
                    <td>{{ $post->status->value }}</td>
                    <td>{{ $post->getTotalEngagement() }}</td>
                    <td>{{ $post->created_at->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="meta" style="margin-top: 30px;">
        Total de publicaciones: {{ $posts->count() }}
    </p>
</body>
</html>
