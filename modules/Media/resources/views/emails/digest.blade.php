<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de actividad Media</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px; background-color: #f9f9f9; margin: 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

        <div style="background-color: #90bb13; padding: 20px 30px;">
            <h1 style="color: #ffffff; margin: 0; font-size: 22px;">Gestor de medios</h1>
        </div>

        <div style="padding: 30px;">
            <h2 style="color: #333333; font-size: 18px;">Hola {{ $user->name }},</h2>
            <p style="color: #555555;">Este es tu resumen de actividad en el gestor de medios.</p>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #555555;">Periodo</td>
                        <td style="padding: 8px 0; color: #333333; font-weight: bold; text-align: right;">{{ ucfirst($stats['period']) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #555555; border-top: 1px solid #e0e0e0;">Archivos subidos</td>
                        <td style="padding: 8px 0; color: #333333; font-weight: bold; text-align: right; border-top: 1px solid #e0e0e0;">{{ $stats['files_uploaded'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #555555; border-top: 1px solid #e0e0e0;">Almacenamiento total</td>
                        <td style="padding: 8px 0; color: #333333; font-weight: bold; text-align: right; border-top: 1px solid #e0e0e0;">{{ round($stats['storage_used'] / 1024 / 1024, 2) }} MB</td>
                    </tr>
                </table>
            </div>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ route('media.index') }}" style="background-color: #90bb13; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">
                    Ir al gestor
                </a>
            </p>

            <p style="color: #888888; font-size: 13px;">
                Recibes este correo porque tienes actividad reciente en el gestor de medios.
            </p>
        </div>

        <div style="background-color: #f9f9f9; padding: 15px 30px; text-align: center;">
            <p style="color: #aaaaaa; font-size: 12px; margin: 0;">{{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
