<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;">

    <div style="background: #fff; border-radius: 8px; padding: 30px; border-top: 4px solid #90bb13;">

        <h2 style="color: #90bb13; margin-top: 0;">
            Comparte tu opinion
        </h2>

        <p style="color: #555;">
            Hola <strong>{{ $send->customer_name }}</strong>,
        </p>

        <div style="color: #555; line-height: 1.6; margin-bottom: 24px;">
            {!! nl2br(e(str_replace('{customer_name}', $send->customer_name, $campaign->message))) !!}
        </div>

        <div style="text-align: center; margin: 28px 0;">
            <a href="{{ $campaign->review_url }}"
               style="background: #90bb13; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: 600; font-size: 16px; display: inline-block;">
                Dejar resena
            </a>
        </div>

        <p style="color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 16px;">
            Este es un correo automatico. No respondas a este mensaje.
        </p>

    </div>

</body>
</html>
