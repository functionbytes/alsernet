<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your ticket has been merged</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #8a6d00; color: #FEC90F; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">Your ticket has been merged</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>Your support ticket has been merged into an existing ticket. All future updates will be tracked there.</p>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Original ticket</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{{ $sourceTicket->ticket_number }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Original subject</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $sourceTicket->subject }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Merged into</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{{ $targetTicket->ticket_number }}</td>
            </tr>
            @if (!empty($reason))
            <tr>
                <td style="padding: 8px; font-weight: bold;">Reason</td>
                <td style="padding: 8px;">{{ $reason }}</td>
            </tr>
            @endif
        </table>
        <p>You can follow up on the merged ticket at the link below:</p>
        <p style="text-align: center; margin: 20px 0;">
            <a href="{{ $mergedTicketUrl }}" style="background: #8a6d00; color: #FEC90F; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                View merged ticket #{{ $targetTicket->ticket_number }}
            </a>
        </p>
        <p style="color: #666; font-size: 13px;">If you have any questions, reply to this email and our team will be happy to help.</p>
    </div>
</body>
</html>
