<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 24px auto; background: #ffffff; border-radius: 6px; overflow: hidden; }
        .header { background-color: #90bb13; padding: 24px 32px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 18px; font-weight: 600; }
        .body { padding: 32px; }
        .body p { line-height: 1.6; margin: 0 0 16px; }
        .footer { padding: 16px 32px; background-color: #f9f9f9; border-top: 1px solid #eeeeee; font-size: 12px; color: #888888; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $emailSubject }}</h1>
        </div>
        <div class="body">
            {!! nl2br(e($body)) !!}
        </div>
        <div class="footer">
            Mensaje enviado automáticamente por el agente de asistencia.
            @if($session->conversation_id)
                Conversación #{{ $session->conversation_id }}.
            @endif
        </div>
    </div>
</body>
</html>
