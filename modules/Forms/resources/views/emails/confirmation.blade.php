<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hemos recibido tu mensaje</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9;padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#90bb13;padding:24px 32px;">
                            <p style="margin:0;color:#ffffff;font-size:20px;font-weight:bold;">
                                {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Icon + Title --}}
                    <tr>
                        <td style="padding:40px 32px 16px;" align="center">
                            <div style="display:inline-block;background-color:#f0f9e8;border-radius:50%;width:64px;height:64px;line-height:64px;text-align:center;margin-bottom:16px;">
                                <span style="font-size:32px;">&#10003;</span>
                            </div>
                            <h1 style="margin:0 0 8px;font-size:24px;color:#1a1a2e;">
                                ¡Hemos recibido tu mensaje!
                            </h1>
                            <p style="margin:0;font-size:14px;color:#6b7280;">
                                Formulario: <strong>{{ $form->name }}</strong>
                            </p>
                        </td>
                    </tr>

                    {{-- Success message --}}
                    <tr>
                        <td style="padding:24px 32px 32px;" align="center">
                            <p style="margin:0;font-size:16px;color:#374151;line-height:1.6;text-align:center;">
                                {{ $successMessage ?? 'Gracias por contactarnos. Hemos recibido tu envío correctamente y nos pondremos en contacto contigo en breve.' }}
                            </p>
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding:0 32px;">
                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:0;">
                        </td>
                    </tr>

                    {{-- Thank you note --}}
                    <tr>
                        <td style="padding:24px 32px 32px;" align="center">
                            <p style="margin:0;font-size:14px;color:#6b7280;">
                                Gracias por confiar en <strong>{{ config('app.name') }}</strong>.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 32px;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">
                                {{ config('app.name') }} &bull;
                                <a href="{{ config('app.url') }}" style="color:#9ca3af;">{{ config('app.url') }}</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
