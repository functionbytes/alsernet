<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - LiveChat Launcher Demo</title>

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
            align-items: flex-start;
            justify-content: center;
            padding: 6rem 2rem 2rem 2rem;
        }

        .demo-content {
            text-align: center;
            color: #333;
            max-width: 600px;
        }

        .demo-content h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2d3748;
        }

        .demo-content p {
            font-size: 1.125rem;
            line-height: 1.8;
            color: #4a5568;
            margin-bottom: 2rem;
        }

        .demo-icon {
            font-size: 4rem;
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                display: none;
            }

            .chat-container {
                background: linear-gradient(135deg, #90bb13 0%, #7a9e10 100%);
            }

            .demo-content h2,
            .demo-content p {
                color: white;
            }
        }

        @media (max-width: 768px) {
            .chat-container {
                padding: 1rem;
            }

            .demo-content h2 {
                font-size: 1.5rem;
            }

            .demo-content p {
                font-size: 1rem;
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

        <!-- Demo Content Container -->
        <div class="chat-container">
            <div class="demo-content">
                <div class="demo-icon">💬</div>
                <h2>Demo del Chat Flotante</h2>
                <p>
                    Haz clic en el botón de chat en la esquina inferior derecha para abrir el widget de soporte.
                    Puedes iniciar una conversación, crear tickets o buscar en nuestra base de conocimientos.
                </p>
            </div>
        </div>
    </div>

    <!-- LiveChat Widget Launcher -->
    <div id="widget-root" data-launcher="true"></div>
    @vite(['resources/js/helpdesk/widget/widget-entry.tsx'])
</body>
</html>
