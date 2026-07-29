<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;">

    <div style="background: #fff; border-radius: 8px; padding: 30px; border-top: 4px solid #13C672;">

        <h2 style="color: #13C672; margin-top: 0;">
            Reporte Analytics: {{ $schedule->name }}
        </h2>

        <p style="color: #555;">
            Adjunto encontrarás el reporte programado generado el
            <strong>{{ now()->format('d/m/Y \a \l\a\s H:i') }}</strong>.
        </p>

        @if (! empty($summary))
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <thead>
                    <tr style="background: #13C672; color: #fff;">
                        <th style="padding: 10px 14px; text-align: left; font-weight: 600;">Métrica</th>
                        <th style="padding: 10px 14px; text-align: right; font-weight: 600;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary as $label => $value)
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 9px 14px;">{{ $label }}</td>
                            <td style="padding: 9px 14px; text-align: right; font-weight: 600;">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p style="color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 16px;">
            Este es un correo automático generado por el sistema.<br>
            Frecuencia configurada: <strong>{{ match($schedule->frequency) {
                'daily'   => 'Diario',
                'weekly'  => 'Semanal',
                'monthly' => 'Mensual',
                default   => $schedule->frequency,
            } }}</strong>
        </p>

    </div>

</body>
</html>
