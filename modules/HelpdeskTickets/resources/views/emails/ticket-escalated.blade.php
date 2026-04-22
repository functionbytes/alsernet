<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket priority escalated</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #FA896B; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">Ticket priority escalated</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>A ticket assigned to you has been automatically escalated due to inactivity. Please review it immediately.</p>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Ticket</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{{ $ticket->ticket_number }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Subject</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $ticket->subject }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Customer</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $ticket->customer_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Priority change</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">
                    <span style="color: #888;">{{ ucfirst($oldPriority) }}</span>
                    &rarr;
                    <strong style="color: #FA896B;">{{ ucfirst($newPriority) }}</strong>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Escalated at</td>
                <td style="padding: 8px;">{{ $ticket->escalated_at?->format('M d, Y H:i') ?? now()->format('M d, Y H:i') }}</td>
            </tr>
        </table>
        <p style="text-align: center; margin: 20px 0;">
            <a href="{{ $ticketUrl }}" style="background: #FA896B; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                Review ticket now
            </a>
        </p>
        <p style="color: #666; font-size: 13px;">Please resolve or update this ticket as soon as possible to maintain service quality.</p>
    </div>
</body>
</html>
