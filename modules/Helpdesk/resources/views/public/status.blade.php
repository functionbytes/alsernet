<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado del servicio</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background:#f7f7fa; }
        .status-card { border:1px solid #e7e7ee; border-radius:10px; }
        .status-dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
        .status-operational .status-dot { background:#13C672; }
        .status-degraded .status-dot { background:#FEC90F; }
        .status-partial_outage .status-dot, .status-major_outage .status-dot { background:#FA896B; }
        .status-maintenance .status-dot { background:#6c757d; }
    </style>
</head>
<body class="py-5">
    <div class="container" style="max-width:780px">
        <div class="text-center mb-5">
            <h1 class="display-5"><i class="fas fa-heart-pulse text-primary"></i> Estado del servicio</h1>
            <p class="text-muted">Última actualización: {{ now()->format('d M Y H:i') }}</p>
            @php
                $allOk = collect($components ?? [])->every(fn($c) => $c->status === 'operational');
            @endphp
            <div class="alert alert-{{ $allOk ? 'success' : 'warning' }} d-inline-block px-4 mt-3">
                <i class="fas fa-{{ $allOk ? 'check-circle' : 'circle-exclamation' }} me-2"></i>
                {{ $allOk ? 'Todos los servicios funcionan con normalidad' : 'Algún servicio está experimentando problemas' }}
            </div>
        </div>

        <h5 class="mb-3">Componentes</h5>
        <div class="status-card bg-white p-3 mb-4">
            @forelse($components ?? [] as $component)
                <div class="d-flex align-items-center justify-content-between py-2 status-{{ $component->status }}">
                    <div>
                        <strong>{{ $component->name }}</strong>
                        @if($component->description)
                            <div class="text-muted small">{{ $component->description }}</div>
                        @endif
                    </div>
                    <div class="text-end">
                        <span class="status-dot me-1"></span>
                        <span class="text-uppercase small">
                            @switch($component->status)
                                @case('operational') Operativo @break
                                @case('degraded') Degradado @break
                                @case('partial_outage') Caída parcial @break
                                @case('major_outage') Caída total @break
                                @case('maintenance') Mantenimiento @break
                            @endswitch
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-3">No hay componentes configurados.</div>
            @endforelse
        </div>

        <h5 class="mb-3">Incidentes activos</h5>
        <div class="status-card bg-white p-3 mb-4">
            @forelse($activeIncidents ?? [] as $incident)
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $incident->title }}</strong>
                        <span class="badge bg-warning">{{ ucfirst($incident->status) }}</span>
                    </div>
                    <div class="small text-muted mt-1">{{ $incident->started_at?->diffForHumans() }}</div>
                    <div class="mt-2">{!! nl2br(e($incident->body)) !!}</div>
                </div>
            @empty
                <div class="text-center text-success py-3"><i class="fas fa-check-circle"></i> Sin incidentes activos</div>
            @endforelse
        </div>

        @if(! empty($pastIncidents) && count($pastIncidents) > 0)
            <h5 class="mb-3 text-muted">Histórico (últimos 90 días)</h5>
            <div class="status-card bg-white p-3">
                @foreach($pastIncidents as $incident)
                    <div class="py-2 border-bottom">
                        <div class="d-flex justify-content-between">
                            <span>{{ $incident->title }}</span>
                            <small class="text-muted">{{ $incident->resolved_at?->format('d M Y') }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
