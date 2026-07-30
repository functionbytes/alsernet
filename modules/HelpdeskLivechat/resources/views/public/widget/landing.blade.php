<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - LiveChat</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('theme/libs/bootstrap/dist/css/bootstrap.min.css') }}">

    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }

        .chat-landing{
            display: flex;
            height: 100vh;
        }


        .chat-landing .sidebar{
            background: linear-gradient(135deg, #90bb13 0%, #7a9e10 100%);
        }


        .sidebar {
            flex: 0 0 40%;
            padding: 4rem 3rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 3rem;
        }

        .sidebar h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .sidebar p {
            font-size: 1.125rem;
            line-height: 1.8;
            opacity: 0.95;
        }

        .chat-container {
            flex: 1;
            background: linear-gradient(to right, rgba(144, 187, 19, 0.2), rgba(144, 187, 19, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .widget-wrapper {
            width: 100%;
            height: 100%;
            max-width: 450px;
            max-height: 700px;
        }

        .widget-wrapper iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                display: none;
            }

            .chat-container {
                background: linear-gradient(135deg, #90bb13 0%, #7a9e10 100%);
            }
        }

        @media (max-width: 768px) {
            .widget-wrapper {
                max-width: 100%;
                max-height: 100%;
            }

            .chat-container {
                padding: 1rem;
            }
        }
    </style>
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
