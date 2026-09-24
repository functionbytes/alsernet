<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket recurrente creado</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #90bb13; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">Ticket recurrente creado</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>Se ha creado automáticamente un ticket recurrente a partir de una plantilla programada.</p>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Ticket</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{{ $ticket->ticket_number }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Asunto</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $ticket->subject }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Programación</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $scheduleName }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Asignado a</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $assignedTo ?? 'Sin asignar' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Creado el</td>
                <td style="padding: 8px;">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
        <p style="text-align: center; margin: 20px 0;">
            <a href="{{ $ticketUrl }}" style="background: #90bb13; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                Ver ticket #{{ $ticket->ticket_number }}
            </a>
        </p>
        <p style="color: #666; font-size: 13px;">Este ticket se generó automáticamente por el sistema de tickets recurrentes.</p>
    </div>
</body>
</html>
