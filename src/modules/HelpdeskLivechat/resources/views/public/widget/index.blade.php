<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LiveChat Widget</title>

    {{-- Vite React App --}}
    @vite(['resources/js/helpdesk/widget/widget-entry.tsx'])

    <style>
        body {
            margin: 0;
            padding: 0;
            @if($isInline ?? false)
            overflow: hidden;
            height: 100vh;
            @endif
        }
    </style>
</head>
<body>
    <div id="widget-root"
         data-preview="{{ ($isPreview ?? false) ? 'true' : 'false' }}"
         data-inline="{{ ($isInline ?? false) ? 'true' : 'false' }}"
         data-conversation-id="{{ $conversationId ?? '' }}"></div>
</body>
</html>
