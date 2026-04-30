<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¿Cómo fue tu experiencia?</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #5D87FF;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .message {
            font-size: 15px;
            color: #666;
            margin-bottom: 30px;
        }
        .rating-container {
            text-align: center;
            margin: 30px 0;
        }
        .rating-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        .rating-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .rating-button {
            display: inline-block;
            width: 60px;
            height: 60px;
            line-height: 60px;
            text-align: center;
            background-color: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 50%;
            font-size: 32px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .rating-button:hover {
            background-color: #5D87FF;
            border-color: #5D87FF;
            transform: scale(1.1);
        }
        .rating-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 12px;
            color: #999;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #666;
        }
        .footer a {
            color: #5D87FF;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¿Cómo fue tu experiencia?</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Hola {{ $contactName }},
            </div>

            <div class="message">
                Gracias por contactarnos recientemente sobre <strong>{{ $conversationSubject }}</strong>.
                <br><br>
                Nos encantaría saber cómo fue tu experiencia con nuestro equipo de soporte. Tu opinión nos ayuda a mejorar.
            </div>

            <div class="rating-container">
                <div class="rating-title">
                    ¿Cómo calificarías tu experiencia?
                </div>

                <div class="rating-buttons">
                    <a href="{{ url('/api/csat/' . $surveyId . '/rate/1') }}" class="rating-button">😞</a>
                    <a href="{{ url('/api/csat/' . $surveyId . '/rate/2') }}" class="rating-button">😕</a>
                    <a href="{{ url('/api/csat/' . $surveyId . '/rate/3') }}" class="rating-button">😐</a>
                    <a href="{{ url('/api/csat/' . $surveyId . '/rate/4') }}" class="rating-button">🙂</a>
                    <a href="{{ url('/api/csat/' . $surveyId . '/rate/5') }}" class="rating-button">😀</a>
                </div>

                <div class="rating-labels">
                    <span>Muy insatisfecho</span>
                    <span>Muy satisfecho</span>
                </div>
            </div>
        </div>

        <div class="footer">
            Esta encuesta es confidencial y solo te tomará unos segundos.
            <br>
            ¿Tienes más comentarios? Haz clic en cualquier calificación para compartir tus comentarios.
        </div>
    </div>
</body>
</html>
