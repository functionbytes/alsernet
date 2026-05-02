<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Preferencias de suscripción</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 520px; margin: 60px auto; padding: 32px; color: #333; }
        h1 { color: #b10100; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        label { display: block; margin: 12px 0; }
        button { background: #b10100; color: #fff; border: 0; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px; }
        button:hover { background: #8e0000; }
        .saved { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <h1>Preferencias de suscripción</h1>

    @if (! empty($saved))
        <div class="saved">Tus preferencias se han guardado correctamente.</div>
    @endif

    @if (! $customer)
        <div class="card">
            <p>El enlace ha caducado. Por favor, vuelve a solicitar acceso a tus preferencias.</p>
        </div>
    @else
        <div class="card">
            <p>Configura qué tipo de comunicaciones quieres recibir como <strong>{{ $customer->email }}</strong>:</p>
            <form method="POST" action="">
                @csrf
                <label>
                    <input type="checkbox" name="preferences[promotions]" value="1"
                        @checked((bool) ($customer->attributes['preferences']['promotions'] ?? true))>
                    Promociones y descuentos exclusivos
                </label>
                <label>
                    <input type="checkbox" name="preferences[news]" value="1"
                        @checked((bool) ($customer->attributes['preferences']['news'] ?? true))>
                    Novedades y nuevos productos
                </label>
                <label>
                    <input type="checkbox" name="preferences[recommendations]" value="1"
                        @checked((bool) ($customer->attributes['preferences']['recommendations'] ?? false))>
                    Recomendaciones personalizadas
                </label>
                <button type="submit">Guardar preferencias</button>
            </form>
        </div>
    @endif
</body>
</html>
