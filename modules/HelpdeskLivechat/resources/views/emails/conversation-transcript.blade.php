<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transcript de conversación</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#b10100;padding:24px 32px;text-align:center;">
                            @if($conversation->inbox?->channel?->logo_url)
                                <img src="{{ $conversation->inbox->channel->logo_url }}"
                                     alt="Logo"
                                     height="40"
                                     style="max-height:40px;display:inline-block;">
                            @else
                                <span style="color:#ffffff;font-size:20px;font-weight:bold;">
                                    Soporte
                                </span>
                            @endif
                        </td>
                    </tr>

                    {{-- Title --}}
                    <tr>
                        <td style="padding:28px 32px 16px;">
                            <h1 style="margin:0 0 8px;font-size:20px;color:#1a1a1a;">
                                Transcript de tu conversación
                            </h1>
                            <p style="margin:0;font-size:13px;color:#666666;">
                                Conversación #{{ $conversation->id }}
                                &mdash;
                                {{ $conversation->created_at?->format('d/m/Y H:i') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Messages --}}
                    <tr>
                        <td style="padding:0 32px 24px;">
                            @forelse($conversation->items as $item)
                                @php
                                    $isAgent = ! is_null($item->user_id);
                                    $senderName = $isAgent
                                        ? (optional($item->user)->name ?? 'Agente')
                                        : (optional($item->author)->name ?? 'Visitante');
                                @endphp
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                                    <tr>
                                        <td>
                                            <p style="margin:0 0 4px;font-size:12px;color:#999999;">
                                                <strong style="color:{{ $isAgent ? '#b10100' : '#333333' }};">
                                                    {{ $senderName }}
                                                </strong>
                                                &nbsp;&middot;&nbsp;
                                                {{ $item->created_at?->format('d/m/Y H:i') }}
                                            </p>
                                            <div style="
                                                background:{{ $isAgent ? '#fff5f5' : '#f8f8f8' }};
                                                border-left:3px solid {{ $isAgent ? '#b10100' : '#cccccc' }};
                                                padding:10px 14px;
                                                border-radius:0 4px 4px 0;
                                                font-size:14px;
                                                color:#333333;
                                                line-height:1.5;
                                            ">
                                                {!! nl2br(e($item->body)) !!}
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            @empty
                                <p style="font-size:14px;color:#999999;text-align:center;">
                                    No hay mensajes en esta conversación.
                                </p>
                            @endforelse
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f9f9f9;border-top:1px solid #eeeeee;padding:20px 32px;text-align:center;">
                            <p style="margin:0 0 6px;font-size:12px;color:#999999;">
                                Este transcript fue solicitado para el correo
                                <strong>{{ $recipientEmail }}</strong>.
                            </p>
                            <p style="margin:0;font-size:11px;color:#bbbbbb;">
                                Si no solicitaste este correo, puedes ignorarlo de forma segura.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
