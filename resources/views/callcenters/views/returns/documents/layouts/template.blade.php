<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Documento PDF' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #007bff;
            font-size: 24px;
            margin: 0;
        }
        
        .content {
            margin-bottom: 30px;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        table th,
        table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mb-3 { margin-bottom: 15px; }
        .mt-3 { margin-top: 15px; }
        
        @page {
            margin: 20px;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="header">
        @yield('header')
    </div>
    
    <div class="content">
        @yield('content')
    </div>
    
    <div class="footer">
        @yield('footer', 'Generado el ' . now()->format('d/m/Y H:i:s') . ' | A-alvarez')
    </div>
</body>
</html>