<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SLA reanudado para el ticket</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #13C672; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">SLA reanudado para el ticket</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>El temporizador de SLA del siguiente ticket se ha reanudado tras un periodo de pausa.</p>
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
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Duración de la pausa</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $pausedMinutes }} minutos</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Nueva fecha límite de SLA</td>
                <td style="padding: 8px; color: #13C672;">{{ $newSlaDueAt }}</td>
            </tr>
        </table>
        <p style="color: #666; font-size: 14px;">El SLA está corriendo de nuevo. Asegúrate de resolver este ticket antes de la nueva fecha límite.</p>
    </div>
</body>
</html>
