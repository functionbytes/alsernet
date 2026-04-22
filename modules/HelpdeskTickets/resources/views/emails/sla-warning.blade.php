<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SLA Warning</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #FEC90F; color: #333; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">SLA Warning — {{ round($percentUsed) }}% of time used</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>The following ticket is approaching its SLA resolution deadline:</p>
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
                <td style="padding: 8px; font-weight: bold;">Due At</td>
                <td style="padding: 8px; color: #d68910;">{{ $ticket->sla_resolution_due_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
            </tr>
        </table>
        <p style="color: #666; font-size: 14px;">Please resolve this ticket soon to avoid an SLA breach.</p>
    </div>
</body>
</html>
