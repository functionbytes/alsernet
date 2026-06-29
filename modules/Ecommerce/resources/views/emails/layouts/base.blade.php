<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:Helvetica,Arial,sans-serif;color:#333;line-height:1.5;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f4f4f7;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                <tr>
                    <td style="background:#90bb13;padding:24px;text-align:center;">
                        <a href="{{ url('/') }}" style="color:#fff;text-decoration:none;font-size:22px;font-weight:700;">{{ config('app.name', 'Tienda') }}</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px 24px;color:#333;font-size:15px;">
                        {!! $slot ?? ($content ?? '') !!}
                    </td>
                </tr>
                <tr>
                    <td style="background:#1a2030;padding:18px 24px;text-align:center;color:rgba(255,255,255,0.7);font-size:12px;">
                        <p style="margin:0 0 6px;">&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
                        <p style="margin:0;">
                            <a href="{{ url('/tienda') }}" style="color:#90bb13;text-decoration:none;">Visitar tienda</a>
                            &nbsp;&middot;&nbsp;
                            <a href="{{ route('shop.legal.show', 'privacy') }}" style="color:#90bb13;text-decoration:none;">Privacidad</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
