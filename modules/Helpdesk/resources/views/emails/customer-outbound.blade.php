<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $conversation->subject ?? __('helpdesk::helpdesk.messages.message_from_support') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#b10100;padding:24px 32px;">
                            <p style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">
                                {{ config('app.name') }}
                            </p>
                            <p style="margin:4px 0 0;color:#e8f5c0;font-size:13px;">
                                {{ __('helpdesk::helpdesk.labels.support_team') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            {!! $bodyHtml !!}
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding:0 32px;">
                            <hr style="border:none;border-top:1px solid #e8e8e8;margin:0;">
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 32px;background:#f9f9f9;">
                            <p style="margin:0;color:#888;font-size:12px;line-height:1.6;">
                                {{ __('helpdesk::helpdesk.email.footer_note', ['app' => config('app.name')]) }}
                                @if($conversation->id)
                                    <br>{{ __('helpdesk::helpdesk.labels.conversation_ref') }}: #{{ $conversation->id }}
                                @endif
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
