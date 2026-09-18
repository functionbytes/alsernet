<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - LiveChat</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('theme/libs/bootstrap/dist/css/bootstrap.min.css') }}">

    <link rel="stylesheet" href="{{ asset('modules/helpdesklivechat/css/widget-landing.css') }}?v={{ filemtime(public_path('modules/helpdesklivechat/css/widget-landing.css')) }}">
</head>
<body>
    <div class="chat-landing">
        <!-- Sidebar with info -->
        <div class="sidebar">
            <div class="logo">
                {{ config('app.name') }}
            </div>
            <div>
                <h1>¿Necesitas ayuda?</h1>
                <p>
                    Nuestro equipo de soporte está disponible para ayudarte.<br>
                    Inicia una conversación y te responderemos lo más pronto posible.
                </p>
            </div>
        </div>

        <!-- Chat Widget Container -->
        <div class="chat-container">
            <div class="widget-wrapper">
                <iframe
                    src="{{ route('lc.widget') }}?inline=true{{ isset($conversationId) ? '&conversationId=' . $conversationId : '' }}"
                    allow="clipboard-read; clipboard-write; autoplay; microphone *; camera *; display-capture *; picture-in-picture *; fullscreen *;"
                    title="LiveChat Widget">
                </iframe>
            </div>
        </div>
    </div>
</body>
</html>
