@php
    $companyName = theme_option('site_title') ?: config('app.name');
    $companyEmail = theme_option('email') ?: config('mail.from.address');
    $companyPhone = theme_option('phone') ?: '';
    $companyAddress = theme_option('address') ?: '';
    $siteUrl = config('app.url');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva respuesta en {{ $form->name }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9;padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#b10100;padding:24px 32px;">
                            <p style="margin:0;color:#ffffff;font-size:20px;font-weight:bold;">
                                {{ $companyName }}
                            </p>
                        </td>
                    </tr>

                    {{-- Title --}}
                    <tr>
                        <td style="padding:32px 32px 16px;">
                            <h1 style="margin:0 0 8px;font-size:22px;color:#1a1a2e;">
                                Nueva respuesta recibida
                            </h1>
                            <p style="margin:0;font-size:14px;color:#6b7280;">
                                Formulario: <strong>{{ $form->name }}</strong> &bull; Envío <strong>#{{ $submission->id }}</strong>
                            </p>
                        </td>
                    </tr>

                    {{-- Meta info --}}
                    <tr>
                        <td style="padding:0 32px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:6px;padding:12px 16px;">
                                <tr>
                                    <td style="font-size:13px;color:#6b7280;padding:4px 0;">
                                        <strong style="color:#374151;">Fecha:</strong>
                                        {{ $submission->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                                @if($submission->ip_address)
                                <tr>
                                    <td style="font-size:13px;color:#6b7280;padding:4px 0;">
                                        <strong style="color:#374151;">IP:</strong>
                                        {{ $submission->ip_address }}
                                        @if($submission->country)
                                            &mdash; {{ $submission->country }}{{ $submission->city ? ', '.$submission->city : '' }}
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                @if($submission->utm_source)
                                <tr>
                                    <td style="font-size:13px;color:#6b7280;padding:4px 0;">
                                        <strong style="color:#374151;">Fuente:</strong>
                                        {{ $submission->utm_source }}
                                        @if($submission->utm_campaign)
                                            &mdash; {{ $submission->utm_campaign }}
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    {{-- Fields --}}
                    <tr>
                        <td style="padding:0 32px 24px;">
                            <p style="margin:0 0 12px;font-size:15px;font-weight:bold;color:#1a1a2e;">
                                Datos del envío
                            </p>
                            {!! $fieldsTable !!}
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td style="padding:0 32px 32px;" align="center">
                            <a href="{{ route('settings.forms.submissions.show', ['form' => $form->id, 'submission' => $submission->id]) }}"
                               style="display:inline-block;background-color:#b10100;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:6px;font-size:14px;font-weight:bold;">
                                Ver en el panel
                            </a>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 32px;">
                            <p style="margin:0 0 4px;font-size:12px;color:#9ca3af;text-align:center;">
                                {{ $companyName }}
                                @if($companyAddress) &bull; {{ $companyAddress }} @endif
                            </p>
                            <p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">
                                @if($companyPhone) {{ $companyPhone }} &bull; @endif
                                <a href="mailto:{{ $companyEmail }}" style="color:#9ca3af;">{{ $companyEmail }}</a>
                                &bull; <a href="{{ $siteUrl }}" style="color:#9ca3af;">{{ $siteUrl }}</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
