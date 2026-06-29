<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Analytics</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { color: #333; border-bottom: 2px solid #5D87FF; padding-bottom: 10px; }
        h2 { color: #5D87FF; margin-top: 30px; }
        .stats-grid { display: table; width: 100%; margin: 20px 0; }
        .stat-box { display: table-cell; width: 33%; padding: 15px; background: #f8f9fa; border-radius: 5px; margin: 5px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #5D87FF; }
        .stat-label { color: #666; font-size: 11px; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #5D87FF; color: white; padding: 8px; text-align: left; font-size: 11px; }
        td { padding: 6px; border-bottom: 1px solid #ddd; font-size: 11px; }
        .meta { color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Reporte de Analytics</h1>
    <p class="meta">Generado el: {{ $generated_at }}</p>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-value">{{ $totalPosts }}</div>
            <div class="stat-label">Total Publicaciones</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $publishedPosts }}</div>
            <div class="stat-label">Publicadas</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ number_format($totalEngagement) }}</div>
            <div class="stat-label">Engagement Total</div>
        </div>
    </div>

    <h2>Top 10 Publicaciones</h2>
    <table>
        <thead>
            <tr>
                <th>Contenido</th>
                <th>Likes</th>
                <th>Comentarios</th>
                <th>Compartidos</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topPosts as $post)
                <tr>
                    <td>{{ Str::limit($post->content, 50) }}</td>
                    <td>{{ $post->likes_count }}</td>
                    <td>{{ $post->comments_count }}</td>
                    <td>{{ $post->shares_count }}</td>
                    <td><strong>{{ $post->getTotalEngagement() }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
