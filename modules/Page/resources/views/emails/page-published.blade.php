<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }}</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
    <div style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08);">

        {{-- Header --}}
        <div style="background-color: #90bb13; padding: 24px 32px;">
            <p style="color: #ffffff; font-size: 13px; margin: 0; opacity: .85;">
                {{ config('app.name') }}
            </p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <h2 style="color: #1a1a1a; font-size: 22px; margin: 0 0 16px;">{{ $page->title }}</h2>

            @if($page->featured_image)
                <img src="{{ $page->featured_image }}" alt="{{ $page->title }}"
                     style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 6px; margin-bottom: 20px; display: block;">
            @endif

            @if($page->description)
                <p style="color: #555555; font-size: 15px; line-height: 1.7; margin: 0 0 24px;">
                    {{ $page->description }}
                </p>
            @endif

            <a href="{{ $page->url }}"
               style="display: inline-block; background-color: #90bb13; color: #ffffff; padding: 12px 28px;
                      text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px;">
                Leer más
            </a>
        </div>

        {{-- Footer --}}
        <div style="padding: 20px 32px; border-top: 1px solid #eeeeee; background-color: #fafafa;">
            <p style="color: #aaaaaa; font-size: 12px; margin: 0; line-height: 1.6;">
                Recibes este email porque estás suscrito a las novedades de {{ config('app.name') }}.
            </p>
        </div>
    </div>
</body>
</html>
