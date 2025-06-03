?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Actualización de Devolución')</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 20px;
        }
        .logo {
            max-height: 50px;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-completed { background-color: #d1ecf1; color: #0c5460; }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .details-table th, .details-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .details-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .tracking-pixel {
            width: 1px;
            height: 1px;
            display: block;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="logo">
        <h2>@yield('header', 'Actualización de Devolución')</h2>
    </div>

    @yield('content')

    <div class="footer">
        <p>Este es un email automático, por favor no responda a este mensaje.</p>
        <p>Si tiene alguna pregunta, contacte con nuestro <a href="{{ route('contact') }}">servicio de atención al cliente</a>.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
    </div>
</div>

@if(isset($tracking_url))
    <img src="{{ $tracking_url }}" alt="" class="tracking-pixel">
@endif
</body>
</html>
