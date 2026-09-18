<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Continúa tu conversación</title>
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
                            <p style="margin:4px 0 0;color:#ffd0d0;font-size:13px;">
                                Soporte al cliente
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
                                Tu conversación quedó pendiente. Haz clic en el botón de abajo para retomarla y continuar donde lo dejaste.
                            </p>
                            @if($conversation->subject)
                                <p style="margin:0 0 24px;font-size:13px;color:#888888;">
                                    Asunto: <strong>{{ $conversation->subject }}</strong>
                                </p>
                            @endif

                            {{-- CTA Button --}}
                            <table cellpadding="0" cellspacing="0" style="margin:24px 0;">
                                <tr>
                                    <td style="background:#90bb13;border-radius:6px;padding:0;">
                                        <a href="{{ $continueUrl }}"
                                           style="display:inline-block;padding:14px 32px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;border-radius:6px;">
                                            Retomar conversación
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0;font-size:13px;color:#888888;line-height:1.6;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                                <a href="{{ $continueUrl }}" style="color:#90bb13;word-break:break-all;">{{ $continueUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding:0 32px;">
                            <hr style="border:none;border-top:1px solid #e8e8e8;margin:0;">
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 32px;background:#f9f9f9;">
                            <p style="margin:0;color:#aaaaaa;font-size:12px;line-height:1.6;">
                                Este enlace es válido por 7 días y solo puede utilizarse una vez.
                                Si no iniciaste esta conversación, puedes ignorar este mensaje con seguridad.
                                @if($conversation->id)
                                    <br>Referencia de conversación: #{{ $conversation->id }}
                                @endif
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
