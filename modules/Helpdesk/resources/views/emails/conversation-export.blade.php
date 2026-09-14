<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportación de conversación</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#90bb13;padding:24px 32px;">
                            <p style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">
                                {{ config('app.name') }}
                            </p>
                            <p style="margin:4px 0 0;color:#eaffb0;font-size:13px;">
                                Exportación de conversación
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;color:#333333;line-height:1.6;">
                                Hola,
                            </p>
                            <p style="margin:0 0 16px;font-size:15px;color:#555555;line-height:1.6;">
                                Adjuntamos la exportación que solicitaste de la conversación
                                <strong>{{ $conversation->subject ?: '#'.$conversation->id }}</strong>.
                            </p>
                            <p style="margin:0;font-size:13px;color:#888888;">
                                Referencia de conversación: #{{ $conversation->id }}
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 32px;background:#f9f9f9;">
                            <p style="margin:0;color:#aaaaaa;font-size:12px;line-height:1.6;">
                                Este correo se generó porque solicitaste una exportación desde el panel de {{ config('app.name') }}.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
