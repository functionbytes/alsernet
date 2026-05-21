{{-- Minimal view: renders the campaign HTML directly (no panel layout) --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $campaign->name }}</title>
</head>
<body style="margin:0;padding:0">
    {!! $html !!}
</body>
</html>
