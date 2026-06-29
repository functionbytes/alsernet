<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirma tu suscripción</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:6px;overflow:hidden;max-width:600px;width:100%;">
                    <tr>
                        <td style="background:#90bb13;padding:24px 32px;">
                            <p style="margin:0;color:#ffffff;font-size:20px;font-weight:bold;">Confirma tu suscripción</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:15px;color:#333333;">
                                Hola{{ $customer->first_name ? ', '.$customer->first_name : '' }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;color:#555555;line-height:1.6;">
                                Hemos recibido tu solicitud de suscripción a nuestras comunicaciones de marketing.
                                Para confirmar y empezar a recibir nuestros emails, haz clic en el botón a continuación.
                            </p>
                            <table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding:8px 0 24px;">
                                        <a href="{{ $confirmUrl }}"
                                           style="display:inline-block;background:#90bb13;color:#ffffff;font-size:15px;font-weight:bold;padding:14px 32px;text-decoration:none;border-radius:4px;">
                                            Confirmar suscripción
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 8px;font-size:13px;color:#888888;line-height:1.5;">
                                Si no puedes hacer clic en el botón, copia y pega este enlace en tu navegador:
                            </p>
                            <p style="margin:0 0 24px;font-size:12px;color:#aaaaaa;word-break:break-all;">
                                {{ $confirmUrl }}
                            </p>
                            <hr style="border:none;border-top:1px solid #eeeeee;margin:24px 0;">
                            <p style="margin:0;font-size:12px;color:#aaaaaa;line-height:1.5;">
                                Si no solicitaste esta suscripción, ignora este correo. No realizaremos ninguna acción sin tu confirmación.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
