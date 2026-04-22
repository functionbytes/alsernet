<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h2 { margin-bottom: 4px; }
        p { margin: 0 0 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #fafafa; }
    </style>
</head>
<body>
    <h2>Tickets — {{ now()->format('Y-m-d') }}</h2>
    <p>Total: {{ $tickets->count() }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Titulo</th>
                <th>Estado</th>
                <th>Prioridad</th>
                <th>Cliente</th>
                <th>Agente</th>
                <th>Creado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $t)
                <tr>
                    <td>{{ $t->ticket_number }}</td>
                    <td>{{ \Str::limit($t->title, 40) }}</td>
                    <td>{{ $t->status?->name ?? '' }}</td>
                    <td>{{ $t->priority }}</td>
                    <td>{{ \Str::limit($t->customer?->name ?? '', 25) }}</td>
                    <td>{{ \Str::limit($t->assignee?->name ?? '', 20) }}</td>
                    <td>{{ $t->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
