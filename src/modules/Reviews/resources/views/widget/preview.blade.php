<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista previa - Widget de reseñas</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f7fafc;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 20px;
            min-height: 100vh;
        }
        .preview-wrapper {
            width: 100%;
            max-width: 640px;
        }
        .preview-header {
            margin-bottom: 24px;
            padding: 16px 20px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .preview-header h2 {
            font-size: 16px;
            margin: 0 0 4px;
            color: #2d3748;
        }
        .preview-header p {
            font-size: 13px;
            color: #718096;
            margin: 0;
        }
        .widget-container {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="preview-wrapper">
        <div class="preview-header">
            <h2>Vista previa del widget</h2>
            <p>
                Ubicacion: <strong>{{ $widgetToken->location->name ?? '—' }}</strong> &mdash;
                Token: <code>{{ substr($widgetToken->token, 0, 12) }}…</code>
            </p>
        </div>
        <div class="widget-container">
            <div data-reviews-widget></div>
        </div>
    </div>
    <script src="{{ $jsUrl }}"></script>
</body>
</html>
