<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[Alerta] {{ $threshold->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #FA896B; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 4px 0 0; font-size: 13px; opacity: .85; }
        .body { padding: 28px 32px; }
        .alert-box { background: #fff8f6; border-left: 4px solid #FA896B; border-radius: 4px; padding: 16px 20px; margin-bottom: 20px; }
        .alert-box h2 { margin: 0 0 6px; font-size: 16px; color: #333; }
        .alert-box p { margin: 0; color: #555; font-size: 14px; line-height: 1.5; }
        .meta { font-size: 13px; color: #888; margin-top: 20px; }
        .meta span { display: inline-block; margin-right: 16px; }
        .footer { background: #f9f9f9; padding: 16px 32px; font-size: 12px; color: #aaa; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Alerta del sistema</h1>
            <p>{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="body">
            <div class="alert-box">
                <h2>{{ $threshold->name }}</h2>
                <p>{{ $data['description'] ?? 'Se ha superado el umbral configurado.' }}</p>
            </div>

            <p style="font-size:14px; color:#444;">
                Esta alerta fue disparada porque las condiciones configuradas para
                <strong>{{ $threshold->name }}</strong> se cumplieron.
            </p>

            @if(!empty($data['count']))
                <p style="font-size:14px; color:#444;">
                    <strong>Total afectados:</strong> {{ number_format($data['count']) }}
                </p>
            @endif

            <div class="meta">
                <span><strong>Tipo:</strong> {{ $threshold->type }}</span>
                <span><strong>Cooldown:</strong> {{ $threshold->cooldown_minutes }} min</span>
            </div>
        </div>
        <div class="footer">
            Este mensaje fue generado automáticamente. No responder a este correo.
            Para gestionar las alertas, ingresa a la plataforma.
        </div>
    </div>
</body>
</html>
