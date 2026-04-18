<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Respuesta #{{ $submission->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        h1 {
            font-size: 18px;
            color: #222;
            margin-bottom: 4px;
        }
        .subtitle {
            font-size: 13px;
            color: #666;
            margin-bottom: 20px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .meta-table td {
            padding: 6px 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .meta-table td:first-child {
            font-weight: bold;
            background-color: #f5f5f5;
            width: 35%;
        }
        .fields-table {
            width: 100%;
            border-collapse: collapse;
        }
        .fields-table th {
            background-color: #000000;
            color: #fff;
            padding: 8px 10px;
            text-align: left;
            font-size: 12px;
        }
        .fields-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        .fields-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #aaa;
            text-align: center;
        }
    </style>
</head>
<body>

    <h1>Respuesta de formulario #{{ $submission->id }}</h1>
    <div class="subtitle">{{ $form->name }}</div>

    <table class="meta-table">
        <tr>
            <td>Formulario</td>
            <td>{{ $form->name }}</td>
        </tr>
        <tr>
            <td>Fecha de envío</td>
            <td>{{ $submission->created_at?->format('d/m/Y H:i:s') }}</td>
        </tr>
        <tr>
            <td>Estado</td>
            <td>{{ ucfirst(str_replace('_', ' ', $submission->status ?? 'new')) }}</td>
        </tr>
        <tr>
            <td>Dirección IP</td>
            <td>{{ $submission->ip_address ?? '—' }}</td>
        </tr>
        <tr>
            <td>País</td>
            <td>{{ $submission->country ?? '—' }}</td>
        </tr>
        @if ($submission->user)
            <tr>
                <td>Usuario</td>
                <td>{{ $submission->user->name }} ({{ $submission->user->email }})</td>
            </tr>
        @endif
        @if ($submission->assignedTo)
            <tr>
                <td>Asignado a</td>
                <td>{{ $submission->assignedTo->name }}</td>
            </tr>
        @endif
    </table>

    <table class="fields-table">
        <thead>
            <tr>
                <th style="width: 35%;">Campo</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($submission->values as $value)
                <tr>
                    <td>{{ $value->field_label ?: $value->field_key }}</td>
                    <td>{{ $value->getDisplayValue() ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" style="text-align: center; color: #999;">Sin datos registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
