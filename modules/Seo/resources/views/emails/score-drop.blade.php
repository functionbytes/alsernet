<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta SEO: bajada de puntuación</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:6px; overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#e53e3e; padding:24px 32px;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:bold;">
                                Alerta SEO: bajada de puntuación
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; color:#333333; font-size:15px;">
                                Se ha detectado una bajada significativa en la puntuación SEO de la siguiente página:
                            </p>

                            <!-- Page info -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9f9f9; border:1px solid #e2e2e2; border-radius:4px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0 0 8px; color:#555555; font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">Página</p>
                                        <p style="margin:0; color:#111111; font-size:16px; font-weight:bold;">{{ $meta->title }}</p>
                                    </td>
                                </tr>
                                @if($meta->url ?? $meta->path ?? null)
                                <tr>
                                    <td style="padding:0 20px 16px;">
                                        <p style="margin:0 0 4px; color:#555555; font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">URL / Ruta</p>
                                        <p style="margin:0; color:#333333; font-size:14px;">{{ $meta->url ?? $meta->path }}</p>
                                    </td>
                                </tr>
                                @endif
                            </table>

                            <!-- Score comparison -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td width="33%" align="center" style="padding:16px; background-color:#e8f5e9; border-radius:4px;">
                                        <p style="margin:0 0 4px; color:#555555; font-size:12px; text-transform:uppercase;">Puntuación anterior</p>
                                        <p style="margin:0; color:#2e7d32; font-size:28px; font-weight:bold;">{{ $previousScore }}</p>
                                    </td>
                                    <td width="33%" align="center" style="padding:16px;">
                                        <p style="margin:0; color:#999999; font-size:24px;">→</p>
                                    </td>
                                    <td width="33%" align="center" style="padding:16px; background-color:#ffebee; border-radius:4px;">
                                        <p style="margin:0 0 4px; color:#555555; font-size:12px; text-transform:uppercase;">Puntuación actual</p>
                                        <p style="margin:0; color:#c62828; font-size:28px; font-weight:bold;">{{ $currentScore }}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Difference -->
                            <p style="margin:0 0 24px; color:#555555; font-size:15px;">
                                Diferencia: <strong style="color:#c62828;">-{{ $previousScore - $currentScore }} puntos</strong>
                            </p>

                            <!-- CTA -->
                            <a href="{{ route('settings.seo.metas.edit', $meta->id) }}"
                               style="display:inline-block; background-color:#90bb13; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:4px; font-size:15px; font-weight:bold;">
                                Ver y editar meta SEO
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
