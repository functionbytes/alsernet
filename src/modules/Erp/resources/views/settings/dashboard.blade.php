@extends('layouts.theme')

@section('page_title', 'Dashboard ERP')

@section('content')

    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0 fw-bold">Panel de Control ERP</h2>
                    <small class="text-muted">
                        <i class="fa fa-sync me-2"></i>
                        <span>Últimas 24 horas de actividad | Período: {{ $metrics['timeRange']['days'] }} días</span>
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" id="daySelector" style="width: 150px;">
                        <option value="7" {{ $days == 7 ? 'selected' : '' }}>Últimos 7 días</option>
                        <option value="15" {{ $days == 15 ? 'selected' : '' }}>Últimos 15 días</option>
                        <option value="30" {{ $days == 30 ? 'selected' : '' }}>Últimos 30 días</option>
                        <option value="60" {{ $days == 60 ? 'selected' : '' }}>Últimos 60 días</option>
                        <option value="90" {{ $days == 90 ? 'selected' : '' }}>Últimos 90 días</option>
                    </select>
                </div>
            </div>
            <hr class="my-3">
        </div>
    </div>

    {{-- Alerts --}}
    @if(!empty($metrics['alerts']) && count($metrics['alerts']) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="fa fa-exclamation-triangle me-2"></i> Alertas activas
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($metrics['alerts'] as $alert)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="alert alert-{{ $alert['type'] }} mb-0 h-100">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0 me-2">
                                            @if($alert['priority'] === 'high')
                                                <i class="fa fa-exclamation-circle fs-4"></i>
                                            @elseif($alert['priority'] === 'medium')
                                                <i class="fa fa-exclamation-triangle fs-4"></i>
                                            @else
                                                <i class="fa fa-info-circle fs-4"></i>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="alert-heading mb-1">{{ $alert['title'] }}</h6>
                                            <p class="mb-0 small">{{ $alert['message'] }}</p>
                                            @if(isset($alert['action']))
                                                <small class="d-block mt-1 fw-bold">
                                                    <i class="fa fa-arrow-right"></i> {{ $alert['action'] }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Overview Statistics --}}
    <div class="row mb-4">
        {{-- Total Endpoints --}}
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 small">Total de endpoints</h6>
                            <h3 class="mb-0">{{ $metrics['overview']['total_endpoints'] }}</h3>
                            <small class="text-muted d-block mt-1">
                                <i class="fa fa-circle me-1" style="color: #90bb13;"></i>
                                {{ $metrics['overview']['active_endpoints'] }} activos
                            </small>
                        </div>
                        <div class="text-primary">
                            <i class="fa fa-cube fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Configured Endpoints --}}
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 small">Configurados</h6>
                            <h3 class="mb-0">{{ $metrics['overview']['configured_endpoints'] }}</h3>
                            <small class="text-muted d-block mt-1">
                                {{ number_format(($metrics['overview']['configured_endpoints'] / $metrics['overview']['total_endpoints']) * 100, 0) }}% del total
                            </small>
                        </div>
                        <div class="text-success">
                            <i class="fa fa-check-circle fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Success Rate --}}
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 small">Tasa de éxito</h6>
                            <h3 class="mb-0">{{ $metrics['performance']['success_rate'] }}%</h3>
                            <small class="d-flex align-items-center mt-1 {{ $metrics['trends']['success_trend']['direction'] === 'up' ? 'text-success' : ($metrics['trends']['success_trend']['direction'] === 'down' ? 'text-danger' : 'text-muted') }}">
                                @if($metrics['trends']['success_trend']['direction'] === 'up')
                                    <i class="fa fa-arrow-up me-1"></i>
                                @elseif($metrics['trends']['success_trend']['direction'] === 'down')
                                    <i class="fa fa-arrow-down me-1"></i>
                                @else
                                    <i class="fa fa-minus me-1"></i>
                                @endif
                                {{ $metrics['trends']['success_trend']['percent'] }}% vs período anterior
                            </small>
                        </div>
                        <div class="text-info">
                            <i class="fa fa-chart-pie fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Calls --}}
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 small">Llamadas totales</h6>
                            <h3 class="mb-0">{{ $metrics['performance']['total_calls'] }}</h3>
                            <small class="d-flex align-items-center mt-1 {{ $metrics['trends']['calls_trend']['direction'] === 'up' ? 'text-success' : ($metrics['trends']['calls_trend']['direction'] === 'down' ? 'text-danger' : 'text-muted') }}">
                                @if($metrics['trends']['calls_trend']['direction'] === 'up')
                                    <i class="fa fa-arrow-up me-1"></i>
                                @elseif($metrics['trends']['calls_trend']['direction'] === 'down')
                                    <i class="fa fa-arrow-down me-1"></i>
                                @else
                                    <i class="fa fa-minus me-1"></i>
                                @endif
                                {{ $metrics['trends']['calls_trend']['percent'] }}% vs período anterior
                            </small>
                        </div>
                        <div class="text-warning">
                            <i class="fa fa-exchange fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Average Response Time --}}
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 small">Tiempo promedio</h6>
                            <h3 class="mb-0">{{ $metrics['performance']['avg_execution_time'] }}ms</h3>
                            <small class="text-muted d-block mt-1">
                                Máx: {{ $metrics['performance']['max_execution_time'] }}ms
                            </small>
                        </div>
                        <div class="text-danger">
                            <i class="fa fa-hourglass fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tokens Generated --}}
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-secondary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1 small">Tokens de acceso</h6>
                            <h3 class="mb-0">{{ $metrics['overview']['tokens_generated'] }}</h3>
                            <small class="text-muted d-block mt-1">
                                Generados
                            </small>
                        </div>
                        <div class="text-secondary">
                            <i class="fa fa-key fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row mb-4">
        {{-- Usage Trend Chart --}}
        <div class="col-lg-8 mb-3">
            <div class="card">
                <div class="card-header py-3 border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-chart-line me-2"></i> Tendencia de uso
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="usageChart" height="60"></canvas>
                </div>
            </div>
        </div>

        {{-- Status Distribution --}}
        <div class="col-lg-4 mb-3">
            <div class="card">
                <div class="card-header py-3 border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-pie-chart me-2"></i> Distribución de estados
                    </h6>
                </div>
                <div class="card-body d-flex justify-content-center">
                    <canvas id="statusChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Second Charts Row --}}
    <div class="row mb-4">
        {{-- Method Distribution --}}
        <div class="col-lg-4 mb-3">
            <div class="card">
                <div class="card-header py-3 border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-sitemap me-2"></i> Métodos HTTP
                    </h6>
                </div>
                <div class="card-body d-flex justify-content-center">
                    <canvas id="methodChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>

        {{-- Top Endpoints --}}
        <div class="col-lg-8 mb-3">
            <div class="card">
                <div class="card-header py-3 border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-star me-2"></i> Top 5 endpoints más utilizados
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40%">Endpoint</th>
                                    <th width="20%" class="text-center">Llamadas</th>
                                    <th width="20%" class="text-center">Exitosas</th>
                                    <th width="20%" class="text-center">Tasa de éxito</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($metrics['topEndpoints'] as $endpoint)
                                    <tr>
                                        <td>
                                            <strong>{{ $endpoint['name'] }}</strong><br>
                                            <small class="text-muted">{{ $endpoint['slug'] }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $endpoint['total_calls'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">{{ $endpoint['success_calls'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-{{ $endpoint['success_rate'] >= 80 ? 'success' : ($endpoint['success_rate'] >= 50 ? 'warning' : 'danger') }}"
                                                     role="progressbar"
                                                     style="width: {{ $endpoint['success_rate'] }}%"
                                                     aria-valuenow="{{ $endpoint['success_rate'] }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ $endpoint['success_rate'] }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No hay datos disponibles
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Errors --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-3 border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-exclamation-circle me-2 text-danger"></i> Errores recientes
                    </h6>
                </div>
                <div class="card-body">
                    @if($metrics['recentErrors']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">Endpoint</th>
                                        <th width="35%">Mensaje de error</th>
                                        <th width="12%">Status</th>
                                        <th width="15%">Tiempo</th>
                                        <th width="13%" class="text-center">Ejecución</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($metrics['recentErrors'] as $error)
                                        <tr class="table-danger">
                                            <td>
                                                <strong>{{ $error['endpoint_name'] }}</strong>
                                            </td>
                                            <td>
                                                <code class="small">{{ Str::limit($error['error_message'], 50) }}</code>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">{{ $error['status_code'] ?? 'Error' }}</span>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $error['created_at']->diffForHumans() }}</small>
                                            </td>
                                            <td class="text-center">
                                                <small class="badge bg-light text-dark">{{ $error['execution_time'] }}ms</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                            <h6 class="fw-bold mb-2">¡Excelente!</h6>
                            <p class="text-muted mb-0">No hay errores registrados en este período.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Usage Chart
    const usageCtx = document.getElementById('usageChart');
    if (usageCtx) {
        new Chart(usageCtx, {
            type: 'line',
            data: {!! json_encode($usageChartData) !!},
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {!! json_encode($statusDistribution) !!},
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                }
            }
        });
    }

    // Method Distribution Chart
    const methodCtx = document.getElementById('methodChart');
    if (methodCtx) {
        new Chart(methodCtx, {
            type: 'radar',
            data: {!! json_encode($methodDistribution) !!},
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: true
                        }
                    }
                }
            }
        });
    }

    // Day selector
    document.getElementById('daySelector')?.addEventListener('change', function(e) {
        window.location.href = `?days=${e.target.value}`;
    });
});
</script>
@endpush
