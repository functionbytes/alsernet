<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta SEO: páginas sin SEO detectadas</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:6px; overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#744210; padding:24px 32px;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:bold;">
                                Alerta SEO: páginas sin SEO detectadas
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; color:#333333; font-size:15px;">
                                Se han detectado páginas sin configuración SEO asociada.
                            </p>

                            <!-- Summary -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef3c7; border:1px solid #f59e0b; border-radius:4px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px;" align="center">
                                        <p style="margin:0 0 8px; color:#92400e; font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">Total de páginas sin SEO</p>
                                        <p style="margin:0; color:#92400e; font-size:40px; font-weight:bold;">{{ $count }}</p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9f9f9; border:1px solid #e2e2e2; border-radius:4px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0 0 4px; color:#555555; font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">Tipo de modelo</p>
                                        <p style="margin:0; color:#111111; font-size:15px; font-weight:bold;">{{ $modelType }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px; color:#555555; font-size:14px;">
                                Se recomienda revisar el módulo "Contenido sin SEO" para asignar configuraciones SEO a estas páginas y mejorar su visibilidad en buscadores.
                            </p>

                            <a href="{{ route('settings.seo.orphans.index') }}"
                               style="display:inline-block; background-color:#90bb13; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:4px; font-size:15px; font-weight:bold;">
                                Ver páginas sin SEO
                            </a>
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
