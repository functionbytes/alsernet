<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;">

    <div style="background: #fff; border-radius: 8px; padding: 30px; border-top: 4px solid #FA896B;">

        <h2 style="color: #FA896B; margin-top: 0;">
            &#9888; Reseña urgente sin respuesta
        </h2>

        <p style="color: #555;">
            Hola <strong>{{ $notifiable->name }}</strong>,<br>
            se ha recibido una reseña de {{ $rating }} {{ $rating === 1 ? 'estrella' : 'estrellas' }} que requiere atención inmediata.
        </p>

        <div style="background: #fff5f5; border-left: 4px solid #FA896B; border-radius: 4px; padding: 16px 20px; margin: 20px 0;">
            <div style="font-size: 22px; letter-spacing: 2px; margin-bottom: 8px;">
                @for ($i = 1; $i <= 5; $i++)
                    <span style="color: {{ $i <= $rating ? '#FEC90F' : '#ddd' }};">&#9733;</span>
                @endfor
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 6px 0; color: #888; width: 130px;">Ubicacion</td>
                    <td style="padding: 6px 0; font-weight: 600;">{{ $review->location->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #888;">Autor</td>
                    <td style="padding: 6px 0;">{{ $review->reviewer_name }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #888;">Fecha</td>
                    <td style="padding: 6px 0;">{{ $review->review_time->format('d/m/Y H:i') }}</td>
                </tr>
            </table>

            @if ($review->comment)
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #fde0d9;">
                    <p style="color: #666; font-style: italic; margin: 0;">&ldquo;{{ $excerpt }}&rdquo;</p>
                </div>
            @endif
        </div>

        <p style="color: #555;">
            Responder a tiempo mejora la reputacion de tu negocio y demuestra compromiso con tus clientes.
        </p>

        <div style="text-align: center; margin: 28px 0;">
            <a href="{{ $reviewUrl }}"
               style="background: #FA896B; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px; display: inline-block;">
                Responder ahora
            </a>
        </div>

        <p style="color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 16px;">
            Este es un correo automatico. No respondas a este mensaje.
        </p>

    </div>

</body>
</html>
