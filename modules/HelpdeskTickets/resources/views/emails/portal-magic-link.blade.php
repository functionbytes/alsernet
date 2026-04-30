<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your portal login link</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8f9fa; padding: 40px 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; margin: 0 auto;">
        <tr>
            <td style="background-color: #ffffff; border-radius: 8px; padding: 40px; border: 1px solid #dee2e6;">
                <h2 style="margin: 0 0 8px; color: #212529; font-size: 20px;">Log in to Support Portal</h2>
                <p style="margin: 0 0 24px; color: #6c757d; font-size: 14px;">Hi {{ $customer->name }},</p>

                <p style="color: #495057; margin: 0 0 24px;">
                    Click the button below to log in to the Support Portal. This link is valid for 24 hours and can only be used once.
                </p>

                <p style="text-align: center; margin: 0 0 24px;">
                    <a
                        href="{{ url('/portal/auth/' . $token) }}"
                        style="display: inline-block; background-color: #b10100; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px;"
                    >
                        Log in to portal
                    </a>
                </p>

                <p style="color: #6c757d; font-size: 13px; margin: 0 0 8px;">
                    Or copy and paste this link into your browser:
                </p>
                <p style="word-break: break-all; font-size: 12px; color: #adb5bd; margin: 0 0 24px;">
                    {{ url('/portal/auth/' . $token) }}
                </p>

                <hr style="border: none; border-top: 1px solid #dee2e6; margin: 24px 0;">

                <p style="color: #adb5bd; font-size: 12px; margin: 0;">
                    This link expires in 24 hours. If you did not request this link, you can safely ignore this email.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
