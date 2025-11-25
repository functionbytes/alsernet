<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recordatorio de documentación</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; background-color: #f5f6fa; margin: 0; padding: 0; color: #333; }
        .wrapper { width: 100%; padding: 30px 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e3e7f0; overflow: hidden; }
        .header { background-color: #0f62fe; color: #ffffff; padding: 24px; text-align: center; }
        .content { padding: 30px; line-height: 1.6; }
        .content h2 { margin-top: 0; color: #0f62fe; }
        .details { margin: 20px 0; padding: 18px; border: 1px solid #e3e7f0; border-radius: 6px; background-color: #f8fafc; }
        .details p { margin: 0 0 8px 0; font-size: 14px; }
        .footer { font-size: 12px; color: #7b8794; padding: 20px 30px 30px; text-align: center; }
        .highlight { font-weight: 600; color: #111827; }
        .cta { display: inline-block; margin-top: 15px; padding: 12px 24px; background-color: #0f62fe; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: 600; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <h1>Necesitamos tu documentación</h1>
        </div>
        <div class="content">
            <h2>Hola {{ $customerName ?? 'cliente' }},</h2>
            <p>Hemos confirmado el pago de tu pedido y necesitamos que completes la subida de la documentación solicitada para continuar con la gestión.</p>

            <div class="details">
                @if($orderReference)
                    <p><span class="highlight">Pedido:</span> {{ $orderReference }}</p>
                @endif
                @if($documentType)
                    <p><span class="highlight">Tipo de documento:</span> {{ ucfirst($documentType) }}</p>
                @endif
                <p><span class="highlight">Código de verificación:</span> {{ $documentUid }}</p>
                @if($uploadDeadline)
                    <p><span class="highlight">Fecha recomendada:</span> {{ $uploadDeadline }}</p>
                @endif
            </div>

            @if($uploadUrl)
                <p>Puedes subir los archivos haciendo clic en el siguiente botón:</p>
                <p style="text-align: center;">
                    <a href="{{ $uploadUrl }}" class="cta" target="_blank" rel="noopener">Subir documentación</a>
                </p>
            @else
                <p>Utiliza el código anterior en nuestro portal de documentación o responde a este correo adjuntando los archivos necesarios.</p>
            @endif

            <p>Si ya enviaste la documentación, por favor ignora este mensaje.</p>
            <p>Gracias por tu colaboración.</p>
        </div>
        <div class="footer">
            Este mensaje es informativo. No respondas a este correo si no necesitas asistencia.
        </div>
    </div>
</div>
</body>
</html>
