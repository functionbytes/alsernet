<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;">

    <div style="background: #fff; border-radius: 8px; padding: 30px; border-top: 4px solid #b10100;">

        <h2 style="color: #b10100; margin-top: 0;">
            Reporte de reseñas listo
        </h2>

        <p style="color: #555;">
            Hola <strong>{{ $report->user?->name ?? 'usuario' }}</strong>,<br>
            tu reporte ha sido generado correctamente el
            <strong>{{ now()->format('d/m/Y \a \l\a\s H:i') }}</strong>.
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tbody>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 9px 14px; color: #888;">Tipo de reporte</td>
                    <td style="padding: 9px 14px; font-weight: 600;">{{ ucfirst(str_replace('_', ' ', $report->report_type)) }}</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 9px 14px; color: #888;">Formato</td>
                    <td style="padding: 9px 14px; font-weight: 600;">{{ strtoupper($report->format) }}</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 9px 14px; color: #888;">Tamaño</td>
                    <td style="padding: 9px 14px; font-weight: 600;">{{ $report->getFileSizeFormatted() }}</td>
                </tr>
            </tbody>
        </table>

        <p style="color: #555;">
            El archivo adjunto contiene los datos exportados según los filtros configurados.
        </p>

        <p style="color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 16px;">
            Este es un correo automático. No respondas a este mensaje.
        </p>

    </div>

</body>
</html>
