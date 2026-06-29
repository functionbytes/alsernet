<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Assigned</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #13C672; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">A ticket has been assigned to you</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>Hi {{ $agent->name }}, the following ticket has been assigned to you:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">Ticket</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{{ $ticket->ticket_number }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Subject</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $ticket->subject }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Customer</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $ticket->customer->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Category</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $ticket->category->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Priority</td>
                <td style="padding: 8px;">{{ ucfirst($ticket->priority ?? 'normal') }}</td>
            </tr>
        </table>
        <p style="margin-top: 20px;">
            <a href="{{ url('/helpdesk/agent/tickets/' . $ticket->ticket_number) }}"
               style="background: #13C672; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
                View Ticket
            </a>
        </p>
    </div>
</body>
</html>
