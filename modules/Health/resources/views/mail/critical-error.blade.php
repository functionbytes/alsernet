<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[ALERT] Error crítico</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #d32f2f; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 4px 0 0; font-size: 13px; opacity: .85; }
        .body { padding: 28px 32px; }
        .error-box { background: #fff5f5; border-left: 4px solid #d32f2f; border-radius: 4px; padding: 16px 20px; margin-bottom: 20px; }
        .error-box h2 { margin: 0 0 8px; font-size: 15px; color: #b71c1c; word-break: break-all; }
        .error-box p { margin: 0; color: #555; font-size: 13px; line-height: 1.6; word-break: break-all; }
        .meta-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 16px; }
        .meta-table td { padding: 8px 12px; border-bottom: 1px solid #eee; vertical-align: top; }
        .meta-table td:first-child { color: #888; white-space: nowrap; width: 110px; }
        .meta-table td:last-child { color: #333; word-break: break-all; }
        .footer { background: #f9f9f9; padding: 16px 32px; font-size: 12px; color: #aaa; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Error crítico detectado</h1>
            <p>{{ config('app.name') }} &mdash; {{ $timestamp }}</p>
        </div>
        <div class="body">
            <div class="error-box">
                <h2>{{ $exceptionClass }}</h2>
                <p>{{ $exceptionMessage }}</p>
            </div>

            <table class="meta-table">
                <tr>
                    <td>URL</td>
                    <td>{{ $url }}</td>
                </tr>
                <tr>
                    <td>Archivo</td>
                    <td>{{ $exceptionFile }}</td>
                </tr>
                <tr>
                    <td>Línea</td>
                    <td>{{ $exceptionLine }}</td>
                </tr>
                <tr>
                    <td>Entorno</td>
                    <td>{{ config('app.env') }}</td>
                </tr>
                <tr>
                    <td>Timestamp</td>
                    <td>{{ $timestamp }}</td>
                </tr>
            </table>
        </div>
        <div class="footer">
            Este mensaje fue generado automáticamente por el manejador de errores. No responder a este correo.
        </div>
    </div>
</body>
</html>
