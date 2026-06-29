@extends('layouts.theme')

@section('title', 'Analítica social')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Analítica social</h1>
        <div class="btn-group">
            <a href="?days=7" class="btn btn-outline-secondary {{ $days == 7 ? 'active' : '' }}">7 días</a>
            <a href="?days=30" class="btn btn-outline-secondary {{ $days == 30 ? 'active' : '' }}">30 días</a>
            <a href="?days=90" class="btn btn-outline-secondary {{ $days == 90 ? 'active' : '' }}">90 días</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h4 class="mb-1">{{ $overview['total_comments'] ?? 0 }}</h4>
                    <small>Comentarios recibidos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h4 class="mb-1">{{ $overview['total_replied'] ?? 0 }}</h4>
                    <small>Respondidos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h4 class="mb-1">{{ ($overview['avg_response_time'] ?? 0) ? round(($overview['avg_response_time'] ?? 0) / 60, 1) . ' min' : '-' }}</h4>
                    <small>Tiempo promedio de respuesta</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h4 class="mb-1">{{ ($overview['total_comments'] ?? 0) > 0 ? round((($overview['total_replied'] ?? 0) / ($overview['total_comments'] ?? 1)) * 100, 1) : 0 }}%</h4>
                    <small>Tasa de respuesta</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">Distribución de intenciones</div>
                <div class="card-body">
                    @if(count($overview['intents_distribution'] ?? []) > 0)
                    <ul class="list-group list-group-flush">
                        @foreach($overview['intents_distribution'] ?? [] as $intent => $count)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ ucfirst($intent) }}
                            <span class="badge bg-primary rounded-pill">{{ $count }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-muted text-center py-3">No hay datos de intenciones</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">Tendencia diaria</div>
                <div class="card-body">
                    @if(count($overview['daily_trend'] ?? []) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr><th>Fecha</th><th class="text-end">Comentarios</th></tr>
                            </thead>
                            <tbody>
                                @foreach($overview['daily_trend'] ?? [] as $day)
                                <tr>
                                    <td>{{ $day->date }}</td>
                                    <td class="text-end">{{ $day->count }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center py-3">No hay datos de tendencia</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
