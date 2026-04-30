<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Received</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #b10100; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">We received your support request</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>Thank you for contacting us. We have received your support request and our team will respond as soon as possible.</p>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">Ticket number</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{{ $ticket->ticket_number }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Subject</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $ticket->subject }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Submitted</td>
                <td style="padding: 8px;">{{ $ticket->created_at->format('M d, Y H:i') }}</td>
            </tr>
        </table>
        <p style="color: #666; font-size: 14px;">Please keep this email for your records. You can reference ticket #{{ $ticket->ticket_number }} in any future correspondence.</p>
    </div>
</body>
</html>
