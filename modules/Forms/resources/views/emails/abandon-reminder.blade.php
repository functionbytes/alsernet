<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu formulario te espera</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f4f4;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                       style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#90bb13;padding:32px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:bold;">
                                ¿Olvidaste algo?
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;">
                            <p style="margin:0 0 16px;color:#333333;font-size:16px;line-height:1.6;">
                                Hola,
                            </p>
                            <p style="margin:0 0 16px;color:#333333;font-size:16px;line-height:1.6;">
                                Dejaste incompleto el formulario <strong>{{ $form->name }}</strong>.
                                No te preocupes, hemos guardado tu progreso y puedes continuar donde lo dejaste.
                            </p>
                            <p style="margin:0 0 32px;color:#333333;font-size:16px;line-height:1.6;">
                                Haz clic en el botón para retomar el formulario:
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                                <tr>
                                    <td style="border-radius:6px;background-color:#90bb13;">
                                        <a href="{{ $restoreUrl }}"
                                           style="display:inline-block;padding:14px 32px;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;border-radius:6px;">
                                            Continuar formulario
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:32px 0 0;color:#888888;font-size:13px;line-height:1.6;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                                <a href="{{ $restoreUrl }}" style="color:#90bb13;word-break:break-all;">{{ $restoreUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f9f9f9;padding:24px 40px;border-top:1px solid #eeeeee;text-align:center;">
                            <p style="margin:0;color:#aaaaaa;font-size:12px;line-height:1.6;">
                                Si no reconoces este mensaje, puedes ignorarlo de forma segura.<br>
                                Este email fue enviado automáticamente, por favor no respondas a este mensaje.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
