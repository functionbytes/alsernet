<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Confirma tu suscripción</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:40px 0;">
        <tr><td align="center">
            <table width="540" cellpadding="0" cellspacing="0" style="background:white;border-radius:8px;overflow:hidden;">
                <tr><td style="padding:32px 40px 0;">
                    <h2 style="margin:0 0 16px;color:#111;">Hola {{ $subscriber->first_name ?? '' }},</h2>
                    <p style="margin:0 0 16px;color:#444;line-height:1.6;font-size:16px;">
                        Gracias por suscribirte a <strong>{{ $list->name }}</strong>.
                        Solo nos falta confirmar tu email haciendo click en el botón:
                    </p>
                </td></tr>
                <tr><td align="center" style="padding:24px 40px 32px;">
                    <a href="{{ $confirmUrl }}"
                       style="display:inline-block;padding:14px 32px;background:#0d6efd;color:white;text-decoration:none;border-radius:6px;font-weight:600;font-size:16px;">
                        Confirmar suscripción
                    </a>
                </td></tr>
                <tr><td style="padding:0 40px 32px;color:#888;font-size:13px;line-height:1.5;">
                    <p style="margin:0 0 8px;">O copia este enlace en tu navegador:</p>
                    <p style="margin:0;word-break:break-all;"><a href="{{ $confirmUrl }}" style="color:#0d6efd;">{{ $confirmUrl }}</a></p>
                    <hr style="border:0;border-top:1px solid #eee;margin:24px 0 16px;">
                    <p style="margin:0;">Si no te suscribiste, ignora este mensaje.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
