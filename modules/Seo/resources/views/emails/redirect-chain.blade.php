<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta SEO: cadenas de redirecciones</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:6px; overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#d69e2e; padding:24px 32px;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:bold;">
                                Alerta SEO: cadenas de redirecciones detectadas
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 24px; color:#333333; font-size:15px;">
                                Se han detectado <strong>{{ count($chains) }}</strong> cadenas de redirecciones que pueden afectar el rendimiento SEO del sitio.
                            </p>

                            @foreach($chains as $index => $chain)
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fffbeb; border:1px solid #f6e05e; border-radius:4px; margin-bottom:12px;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <p style="margin:0 0 6px; color:#92400e; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">
                                            Cadena {{ $index + 1 }}
                                        </p>
                                        <p style="margin:0; color:#333333; font-size:14px; font-family: 'Courier New', monospace; word-break:break-all;">
                                            {{ implode(' → ', $chain) }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endforeach

                            <p style="margin:24px 0 0; color:#555555; font-size:14px;">
                                Se recomienda consolidar estas redirecciones para mejorar la velocidad de carga y evitar pérdidas de autoridad de enlace.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f4f4f4; padding:16px 32px; border-top:1px solid #e2e2e2;">
                            <p style="margin:0; color:#999999; font-size:12px;">
                                Este es un mensaje automático generado por el módulo SEO de {{ config('app.name') }}.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
