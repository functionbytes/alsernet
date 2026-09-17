<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LiveChat Widget</title>

    {{-- Vite React App --}}
    @vite(['resources/js/helpdesk/widget/widget-entry.tsx'])

    <link rel="stylesheet" href="{{ asset('modules/helpdesklivechat/css/widget-dev-shell.css') }}?v={{ filemtime(public_path('modules/helpdesklivechat/css/widget-dev-shell.css')) }}">
</head>
<body class="{{ ($isInline ?? false) ? 'widget-inline' : '' }}">
    <div id="widget-root"
         data-preview="{{ ($isPreview ?? false) ? 'true' : 'false' }}"
         data-inline="{{ ($isInline ?? false) ? 'true' : 'false' }}"
         data-conversation-id="{{ $conversationId ?? '' }}"></div>
</body>
</html>
