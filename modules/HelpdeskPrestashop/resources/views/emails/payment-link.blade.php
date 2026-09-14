<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completa el pago de tu pedido {{ $orderCode }}</title>
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
                            <p style="margin:4px 0 0;color:#e8f5c0;font-size:13px;">
                                Pedido {{ $orderCode }}
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;color:#333;font-size:15px;line-height:1.6;">
                                Hola {{ $customerName }},
                            </p>
                            <p style="margin:0 0 16px;color:#333;font-size:15px;line-height:1.6;">
                                Hemos preparado tu pedido <strong>{{ $orderCode }}</strong> por un total de
                                <strong>{{ $total }}</strong>. Para completarlo, finaliza el pago con el siguiente enlace seguro:
                            </p>
                            <p style="margin:24px 0;text-align:center;">
                                <a href="{{ $paymentUrl }}"
                                   style="display:inline-block;background:#90bb13;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:14px 32px;border-radius:8px;">
                                    Pagar ahora
                                </a>
                            </p>
                            <p style="margin:0;color:#888;font-size:12px;line-height:1.6;word-break:break-all;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                                <a href="{{ $paymentUrl }}" style="color:#90bb13;">{{ $paymentUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 32px;background:#f9f9f9;border-top:1px solid #e8e8e8;">
                            <p style="margin:0;color:#888;font-size:12px;line-height:1.6;">
                                Este enlace de pago fue generado por nuestro equipo de atención al cliente.
                                Si no esperabas este mensaje, puedes ignorarlo.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
