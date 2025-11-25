<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de documentos</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; background-color: #f5f6fa; margin: 0; padding: 0; color: #333; }
        .wrapper { width: 100%; padding: 30px 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e3e7f0; overflow: hidden; }
        .header { background-color: #133759; color: #ffffff; padding: 24px; text-align: center; }
        .content { padding: 30px; line-height: 1.6; }
        .content h2 { margin-top: 0; color: #133759; }
        .details { margin: 20px 0; padding: 18px; border: 1px solid #e3e7f0; border-radius: 6px; background-color: #f8fafc; }
        .details p { margin: 0 0 8px 0; font-size: 14px; }
        .footer { font-size: 12px; color: #7b8794; padding: 20px 30px 30px; text-align: center; }
        .highlight { font-weight: 600; color: #111827; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <h1>Documentación recibida</h1>
        </div>
        <div class="content">
            <h2>Hola {{ $customerName ?? 'cliente' }},</h2>
            <p>Hemos recibido correctamente la documentación solicitada. Nuestro equipo revisará el material a la mayor brevedad y te informaremos cuando el proceso avance.</p>

            <div class="details">
                @if($orderReference)
                    <p><span class="highlight">Pedido:</span> {{ $orderReference }}</p>
                @endif
                @if($documentType)
                    <p><span class="highlight">Tipo de documento:</span> {{ ucfirst($documentType) }}</p>
                @endif
                @if($uploadedAt)
                    <p><span class="highlight">Fecha de carga:</span> {{ $uploadedAt }}</p>
                @endif
            </div>

            <p>Si necesitas modificar o reenviar algún archivo, simplemente responde a este correo o contacta con tu gestor habitual.</p>
            <p>Gracias por confiar en nosotros.</p>
        </div>
        <div class="footer">
            Este mensaje es informativo. No respondas a este correo si no necesitas asistencia.
        </div>
    </div>
</div>
</body>
</html>
