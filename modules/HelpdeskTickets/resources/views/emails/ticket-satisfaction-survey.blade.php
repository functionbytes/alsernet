<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Encuesta de satisfaccion</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #90bb13; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">Como fue tu experiencia?</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>Tu ticket <strong>#{{ $ticket->ticket_number }}</strong> ha sido cerrado.</p>
        <p>Nos gustaria saber como fue tu experiencia con nuestro soporte. Por favor selecciona una puntuacion:</p>

        <div style="text-align: center; margin: 24px 0;">
            @for ($i = 1; $i <= 5; $i++)
                <a href="{{ route('portal.tickets.rate.email', ['ticketNumber' => $ticket->ticket_number, 'rating' => $i]) }}"
                   style="display: inline-block; margin: 0 6px; padding: 12px 20px; background: #f9f9f9; border: 2px solid #ddd;
                          border-radius: 50%; font-size: 22px; text-decoration: none; color: #333; font-weight: bold;">
                    {{ $i }}
                </a>
            @endfor
        </div>

        <p style="text-align: center; font-size: 13px; color: #888;">
            1 = Muy insatisfecho &nbsp;&nbsp; 5 = Muy satisfecho
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #666; font-size: 13px;">
            Ticket: #{{ $ticket->ticket_number }} &mdash; {{ e($ticket->subject) }}<br>
            Cerrado: {{ $ticket->closed_at?->format('d/m/Y H:i') }}
        </p>
    </div>
</body>
</html>
