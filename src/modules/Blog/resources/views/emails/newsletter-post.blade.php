<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4;">
        <tr>
            <td align="center" style="padding:30px 10px;">

                {{-- Container --}}
                <table width="600" cellpadding="0" cellspacing="0" border="0"
                       style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#90bb13;padding:24px 32px;text-align:center;">
                            <span style="color:#ffffff;font-size:22px;font-weight:bold;letter-spacing:0.5px;">
                                {{ config('app.name') }}
                            </span>
                        </td>
                    </tr>

                    {{-- Featured image --}}
                    @if($post->image)
                    <tr>
                        <td style="padding:0;">
                            <img src="{{ $post->image }}"
                                 alt="{{ $post->title }}"
                                 width="600"
                                 style="width:100%;max-width:600px;height:auto;display:block;">
                        </td>
                    </tr>
                    @endif

                    {{-- Content --}}
                    <tr>
                        <td style="padding:32px 32px 24px;">

                            {{-- Title --}}
                            <h1 style="margin:0 0 16px;font-size:24px;font-weight:bold;color:#1a1a1a;line-height:1.3;">
                                {{ $post->title }}
                            </h1>

                            {{-- Meta --}}
                            @if($post->published_at)
                            <p style="margin:0 0 20px;font-size:13px;color:#888888;">
                                {{ $post->published_at->translatedFormat('d \d\e F, Y') }}
                                @if($post->user)
                                    &nbsp;&bull;&nbsp; {{ $post->user->name }}
                                @endif
                            </p>
                            @endif

                            {{-- Excerpt --}}
                            <p style="margin:0 0 28px;font-size:15px;color:#444444;line-height:1.7;">
                                {{ mb_substr(strip_tags($post->description ?: $post->content ?? ''), 0, 300) }}...
                            </p>

                            {{-- CTA --}}
                            <table cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:5px;background-color:#90bb13;">
                                        <a href="{{ $post->url }}"
                                           style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:5px;">
                                            Leer más &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding:0 32px;">
                            <hr style="border:none;border-top:1px solid #eeeeee;margin:0;">
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 32px;text-align:center;">
                            <p style="margin:0 0 8px;font-size:12px;color:#aaaaaa;">
                                Recibes este correo porque estás suscrito al newsletter de {{ config('app.name') }}.
                            </p>
                            <p style="margin:0;font-size:12px;color:#aaaaaa;">
                                <a href="{{ $unsubscribe_link ?? config('blog.unsubscribe_url', '#') }}"
                                   style="color:#90bb13;text-decoration:underline;">
                                    Cancelar suscripción
                                </a>
                            </p>
                        </td>
                    </tr>

                </table>
                {{-- /Container --}}

            </td>
        </tr>
    </table>

</body>
</html>
