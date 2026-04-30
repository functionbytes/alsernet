<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte - Resumen por ubicación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #b10100;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #b10100;
            color: white;
        }
        .summary-box {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Resumen por ubicación</h1>

    <div class="summary-box">
        <p><strong>Periodo:</strong> {{ $data['period']['from'] }} a {{ $data['period']['to'] }}</p>
        <p><strong>Total reseñas:</strong> {{ $data['summary']['total_reviews'] }}</p>
        <p><strong>Rating promedio:</strong> {{ $data['summary']['avg_rating'] }}</p>
        <p><strong>Ubicaciones:</strong> {{ $data['summary']['total_locations'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Ubicación</th>
                <th>Total</th>
                <th>Rating Promedio</th>
                <th>Con Comentario</th>
                <th>Con Respuesta</th>
                <th>Tasa Respuesta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['locations'] as $location)
                <tr>
                    <td>{{ $location['location_name'] }}</td>
                    <td>{{ $location['total_reviews'] }}</td>
                    <td>{{ $location['avg_rating'] }}</td>
                    <td>{{ $location['with_comment'] }}</td>
                    <td>{{ $location['with_reply'] }}</td>
                    <td>{{ $location['reply_rate'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: center; color: #999; font-size: 10px;">
        Generado el {{ now()->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>
