@extends('layouts.theme')

@section('title', 'Analíticas — ' . $chatFlow->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Analíticas del flow'])
@endsection

@section('content')

    @include('core::components.alerts')

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('chatflow.index') }}">Chat flows</a></li>
            <li class="breadcrumb-item"><a href="{{ route('chatflow.edit', $chatFlow) }}">{{ $chatFlow->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Analíticas</li>
        </ol>
    </nav>

    {{-- Flow header --}}
    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <div class="flex-fill">
                <h5 class="fw-bold mb-1">{{ $chatFlow->name }}</h5>
                <p class="small text-muted mb-0">Rendimiento del flow y puntos de abandono</p>
            </div>
            <a href="{{ route('chatflow.sessions', $chatFlow) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-list me-1"></i> Ver sesiones
            </a>
            <a href="{{ route('chatflow.edit', $chatFlow) }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-pen me-1"></i> Editar flow
            </a>
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Total sesiones</h6>
                    <h4 class="mb-1 fw-bold">{{ number_format($summary['total']) }}</h4>
                    <small class="text-muted">Registradas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Tasa de resolución</h6>
                    <h4 class="mb-1 fw-bold">{{ $summary['resolution_rate'] }}%</h4>
                    <small class="text-muted">Completadas + transferidas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Completadas</h6>
                    <h4 class="mb-1 fw-bold">{{ number_format($summary['completed']) }}</h4>
                    <small class="text-muted">{{ number_format($summary['transferred']) }} transferidas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Abandonadas</h6>
                    <h4 class="mb-1 fw-bold">{{ number_format($summary['abandoned'] + $summary['failed']) }}</h4>
                    <small class="text-muted">{{ number_format($summary['active']) }} activas</small>
                </div>
            </div>
        </div>
    </div>

    {{-- AI resolution --}}
    @if(($aiMetrics['used'] ?? 0) > 0)
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold"><i class="fas fa-robot me-2 text-primary"></i>Resolución por IA</h6>
                <p class="small mb-0 text-muted">De las conversaciones que llegaron al nodo de IA</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <h6 class="card-title mb-1 text-muted">Con IA</h6>
                        <h4 class="mb-0 fw-bold">{{ number_format($aiMetrics['used']) }}</h4>
                    </div>
                    <div class="col-6 col-md-3">
                        <h6 class="card-title mb-1 text-muted">Resueltas por el bot</h6>
                        <h4 class="mb-0 fw-bold text-success">{{ number_format($aiMetrics['resolved']) }}</h4>
                    </div>
                    <div class="col-6 col-md-3">
                        <h6 class="card-title mb-1 text-muted">Escaladas a agente</h6>
                        <h4 class="mb-0 fw-bold">{{ number_format($aiMetrics['escalated']) }}</h4>
                    </div>
                    <div class="col-6 col-md-3">
                        <h6 class="card-title mb-1 text-muted">Tasa de autoservicio</h6>
                        <h4 class="mb-0 fw-bold">{{ $aiMetrics['rate'] }}%</h4>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- CSAT --}}
    @if(($csat['answered'] ?? 0) > 0)
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-1 fw-bold"><i class="fas fa-star me-2 text-warning"></i>Satisfacción del cliente (CSAT)</h6>
                <p class="small mb-0 text-muted">Valoraciones que dejaron los clientes al terminar el flow</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <h6 class="card-title mb-1 text-muted">Respuestas</h6>
                        <h4 class="mb-0 fw-bold">{{ number_format($csat['answered']) }}</h4>
                    </div>
                    <div class="col-6 col-md-3">
                        <h6 class="card-title mb-1 text-muted">Puntuación media</h6>
                        <h4 class="mb-0 fw-bold text-info">{{ $csat['average'] }}<small class="text-muted">/{{ $csat['max'] }}</small></h4>
                    </div>
                    <div class="col-6 col-md-3">
                        <h6 class="card-title mb-1 text-muted">Satisfechos</h6>
                        <h4 class="mb-0 fw-bold text-success">{{ number_format($csat['satisfied']) }}</h4>
                    </div>
                    <div class="col-6 col-md-3">
                        <h6 class="card-title mb-1 text-muted">% Satisfacción</h6>
                        <h4 class="mb-0 fw-bold">{{ $csat['rate'] }}%</h4>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Drop-off per node --}}
    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-1 fw-bold">Abandono por nodo</h6>
            <p class="small mb-0 text-muted">Cuántas sesiones llegaron a cada paso y cuántas se quedaron ahí sin terminar</p>
        </div>
        <div class="card-body">
            @if(count($dropOff) > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Paso</th>
                                <th>Tipo</th>
                                <th class="text-end">Alcanzaron</th>
                                <th class="text-end">Abandonaron</th>
                                <th class="dropoff-rate-col">Tasa de abandono</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dropOff as $row)
                                @php
                                    $rate = $row['rate'];
                                    $barColor = $rate >= 50 ? 'danger' : ($rate >= 20 ? 'warning' : 'success');
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $row['label'] }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $row['type'] }}</span></td>
                                    <td class="text-end">{{ number_format($row['reached']) }}</td>
                                    <td class="text-end">{{ number_format($row['dropped']) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-fill dropoff-bar">
                                                <div class="progress-bar bg-{{ $barColor }}" role="progressbar"
                                                     data-rate="{{ $rate }}"
                                                     aria-valuenow="{{ $rate }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-muted dropoff-rate-val">{{ $rate }}%</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-chart-column fa-3x mb-3 text-muted opacity-50"></i>
                    <h6 class="fw-bold mb-2">Aún no hay datos</h6>
                    <p class="text-muted mb-0">Cuando el flow tenga sesiones, aquí verás dónde abandonan los usuarios.</p>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('css')
<style>
.dropoff-rate-col { min-width: 180px; }
.dropoff-bar { height: 8px; }
.dropoff-rate-val { min-width: 42px; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    // Width of each drop-off bar from data-rate (avoids inline styles)
    $('.progress-bar[data-rate]').each(function () {
        $(this).css('width', $(this).data('rate') + '%');
    });
});
</script>
@endpush
