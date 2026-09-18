@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @can('suppliers.monitoring.manage')
        @include('core::components.card', [
            'title'   => $pageTitle,
            'actions' => '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#budget-config-modal"><i class="fas fa-sliders me-1"></i> Configurar presupuestos</button>',
        ])
    @else
        @include('core::components.card', ['title' => $pageTitle])
    @endcan
@endsection

@push('styles')
<style>
/* Estilo tipo "analytics dashboard": sombra sutil + radios más redondeados
   en las cards, pills de rango de fecha tipo cápsula y KPI cards con icono
   circular + mini-gráfica de tendencia. Scoped a .ai-dashboard para no
   afectar el resto del panel. */
.ai-dashboard .card {
    border: 0;
    border-radius: 12px;
    box-shadow: 0 2px 4px -1px rgba(175, 182, 201, .2);
}
.ai-dashboard .stat-card {
    border-radius: 10px;
}

.ai-dashboard .period-pills {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    background: #f4f6fa;
    border-radius: 10px;
    padding: 3px;
}
.ai-dashboard .period-pills .period-btn {
    border: 0;
    background: transparent;
    color: #5a6a85;
    font-weight: 600;
    font-size: .78rem;
    padding: 5px 14px;
    border-radius: 8px;
    transition: background-color .15s, color .15s;
}
.ai-dashboard .period-pills .period-btn:hover:not(.active) {
    background: rgba(144, 187, 19, .1);
}
.ai-dashboard .period-pills .period-btn.active {
    background: #90bb13;
    color: #fff;
    box-shadow: 0 2px 4px rgba(144, 187, 19, .35);
}

.ai-dashboard .kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(144, 187, 19, .12);
    color: #90bb13;
    font-size: .95rem;
    flex-shrink: 0;
}
.ai-dashboard .kpi-change {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .74rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
}
.ai-dashboard .kpi-change.up { background: rgba(76, 175, 80, .12); color: #4caf50; }
.ai-dashboard .kpi-change.down { background: rgba(248, 81, 73, .12); color: #f85149; }
.ai-dashboard .kpi-sparkline-wrap {
    position: relative;
    width: 100%;
    height: 46px;
}
.ai-dashboard .kpi-icon-dyn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .82rem;
    flex-shrink: 0;
}
</style>
@endpush

@section('content')
<div class="widget-content searchable-container list ai-dashboard">
    @include('core::components.alerts')

    {{-- Banner de alertas de presupuesto (servidor — se muestra si algún budget supera el 80%) --}}
    @if($budgetWarning ?? false)
        <div class="mb-3" id="budget-warning-banner">
            @foreach($budgetWarnings as $w)
                @php
                    $providerLabel = ucfirst($w['provider']);
                    $isBlocked     = $w['blocked'];
                    $alertClass    = $w['level'] === 'danger' ? 'alert-danger' : 'alert-warning';
                    $iconClass     = $w['level'] === 'danger' ? 'fa-circle-xmark' : 'fa-triangle-exclamation';
                    $lines         = [];

                    if ($w['monthly_pct'] >= 80) {
                        $lines[] = 'Mensual: ' . number_format($w['monthly_pct'], 1) . '% — $' . number_format($w['monthly_usage'], 4) . ' de $' . number_format($w['monthly_limit'], 2);
                    }
                    if ($w['daily_pct'] >= 80 && $w['daily_limit'] !== null) {
                        $lines[] = 'Diario: ' . number_format($w['daily_pct'], 1) . '% — $' . number_format($w['daily_usage'], 4) . ' de $' . number_format($w['daily_limit'], 2);
                    }
                @endphp
                <div class="alert {{ $alertClass }} alert-dismissible d-flex align-items-start gap-2 mb-2" role="alert">
                    <i class="fas {{ $iconClass }} mt-1 flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <strong>Presupuesto {{ $providerLabel }} al límite</strong>
                        @if($isBlocked)
                            <span class="badge bg-danger ms-2">Bloqueado</span>
                        @endif
                        <ul class="mb-0 mt-1 ps-3" style="font-size:.9em;">
                            @foreach($lines as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Banner de alertas de presupuesto (cargado vía JS) --}}
    <div id="budget-alerts-banner" class="mb-3" style="display:none;"></div>

    @php
        $openaiBudget = $budgets->get('openai');
        $anthropicBudget = $budgets->get('anthropic');
        $openaiPct = $openaiBudget ? $openaiBudget->usagePercentage() : 0;
        $anthropicPct = $anthropicBudget ? $anthropicBudget->usagePercentage() : 0;
        $openaiBarColor = '#13deb9';
        $anthropicBarColor = '#13deb9';
    @endphp

    {{-- Distribución de estado IA --}}
    @php
        $statusRows = [
            [
                ['key' => 'pending_generation', 'label' => 'Pendiente gen.',   'icon' => 'fa-clock',        'color' => '#5a6a85'],
                ['key' => 'generating',         'label' => 'Generando',        'icon' => 'fa-spinner',      'color' => '#2a3547'],
                ['key' => 'pending_validation', 'label' => 'Pend. validación', 'icon' => 'fa-hourglass',    'color' => '#fa8231'],
                ['key' => 'in_review',          'label' => 'En revisión',      'icon' => 'fa-eye',          'color' => '#0dcaf0'],
            ],
            [
                ['key' => 'needs_revision',     'label' => 'Necesita rev.',    'icon' => 'fa-pen',          'color' => '#fd7e14'],
                ['key' => 'validated',          'label' => 'Validado',         'icon' => 'fa-check-circle', 'color' => '#13deb9'],
                ['key' => 'published',          'label' => 'Publicado',        'icon' => 'fa-globe',        'color' => '#4caf50'],
                ['key' => 'rejected',           'label' => 'Rechazado',        'icon' => 'fa-ban',          'color' => '#f85149'],
            ],
        ];
        $funnelTotal = array_sum($funnelStats);
    @endphp


    {{-- KPI stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-lg col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">Costo del mes</h6>
                        <span class="kpi-icon"><i class="fas fa-dollar-sign"></i></span>
                    </div>
                    <h4 class="mb-1 fw-bold">${{ number_format($monthlyStats['total_cost'], 2) }}</h4>
                    @if($monthlyStats['cost_change_pct'] !== null)
                        @php $up = $monthlyStats['cost_change_pct'] >= 0; @endphp
                        <span class="kpi-change {{ $up ? 'up' : 'down' }}">
                            <i class="fas fa-arrow-{{ $up ? 'up' : 'down' }}" style="font-size:.6rem;"></i> {{ abs($monthlyStats['cost_change_pct']) }}%
                        </span>
                        <span class="text-muted" style="font-size:.72rem;"> vs mes ant.</span>
                    @else
                        <small class="text-muted">Sin datos previos</small>
                    @endif
                    <div class="kpi-sparkline-wrap mt-2"><canvas id="spark-cost"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-lg col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">Solicitudes</h6>
                        <span class="kpi-icon"><i class="fas fa-paper-plane"></i></span>
                    </div>
                    <h4 class="mb-1 fw-bold">{{ number_format($monthlyStats['request_count']) }}</h4>
                    @if($monthlyStats['request_change_pct'] !== null)
                        @php $up = $monthlyStats['request_change_pct'] >= 0; @endphp
                        <span class="kpi-change {{ $up ? 'up' : 'down' }}">
                            <i class="fas fa-arrow-{{ $up ? 'up' : 'down' }}" style="font-size:.6rem;"></i> {{ abs($monthlyStats['request_change_pct']) }}%
                        </span>
                        <span class="text-muted" style="font-size:.72rem;"> vs mes ant.</span>
                    @else
                        <small class="text-muted">Sin datos previos</small>
                    @endif
                    <div class="kpi-sparkline-wrap mt-2"><canvas id="spark-requests"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-lg col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">Tokens consumidos</h6>
                        <span class="kpi-icon"><i class="fas fa-coins"></i></span>
                    </div>
                    <h4 class="mb-1 fw-bold">{{ number_format($monthlyStats['total_tokens']) }}</h4>
                    <small class="text-muted">Input + Output</small>
                    <div class="kpi-sparkline-wrap mt-2"><canvas id="spark-tokens"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-lg col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">Latencia media</h6>
                        <span class="kpi-icon"><i class="fas fa-gauge-high"></i></span>
                    </div>
                    <h4 class="mb-1 fw-bold">
                        {{ $monthlyStats['avg_latency_ms'] > 0 ? number_format($monthlyStats['avg_latency_ms']) . 'ms' : '—' }}
                    </h4>
                    <small class="text-muted">Tiempo promedio de respuesta</small>
                    <div class="kpi-sparkline-wrap mt-2"><canvas id="spark-latency"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-lg col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">OpenAI</h6>
                        <span class="kpi-icon"><i class="fas fa-robot"></i></span>
                    </div>
                    <h4 class="mb-1 fw-bold">{{ number_format($openaiPct, 1) }}%</h4>
                    @if($openaiBudget)
                        <small class="text-muted">${{ number_format($openaiBudget->currentMonthUsage(), 2) }} / ${{ number_format($openaiBudget->monthly_limit, 2) }}</small>
                        <div class="mt-2" style="height: 4px; background: #e9ecef; border-radius: 2px;">
                            <div style="height: 4px; width: {{ min($openaiPct, 100) }}%; background: {{ $openaiBarColor }}; border-radius: 2px; transition: width 0.3s;"></div>
                        </div>
                    @else
                        <small class="text-muted">No configurado</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Charts: Line + Doughnut --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div>
                            <h5 class="card-title fw-semibold mb-1">Uso en el tiempo</h5>
                            <p class="card-subtitle text-muted mb-0">Evolucion de costos por dia</p>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="byModelToggle">
                                <label class="form-check-label fs-2" for="byModelToggle">Por modelo</label>
                            </div>
                            <div class="period-pills">
                                <button type="button" class="period-btn active" data-period="7">7d</button>
                                <button type="button" class="period-btn" data-period="30">30d</button>
                                <button type="button" class="period-btn" data-period="90">90d</button>
                            </div>
                        </div>
                    </div>
                    <div style="position: relative; height: 320px;">
                        <canvas id="usageChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="mb-4">
                        <h5 class="card-title fw-semibold mb-1">Distribucion por modelo</h5>
                        <p class="card-subtitle text-muted mb-0">Reparto de costos del mes</p>
                    </div>
                    @if($monthlyStats['total_cost'] > 0)
                        <div class="d-flex align-items-center justify-content-center flex-grow-1">
                            <canvas id="modelChart" style="max-height: 220px;"></canvas>
                        </div>
                        <div class="text-center mt-3 pt-3 border-top">
                            <h4 class="fw-semibold mb-0">${{ number_format($monthlyStats['total_cost'], 4) }}</h4>
                            <span class="fs-2 text-muted">Costo total del mes</span>
                        </div>
                    @else
                        <div class="text-center py-5 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                            <p class="text-muted mb-0">Sin datos de costos este mes</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Operation breakdown --}}
    <div class="card mb-4">
        <div class="card-body p-4">
            <h5 class="card-title fw-semibold mb-1">Desglose por operacion</h5>
            <p class="card-subtitle text-muted mb-4">Costos y solicitudes por tipo de operacion IA</p>

            @if($monthlyStats['request_count'] > 0 && count($monthlyStats['operation_distribution']) > 0)
                @foreach($monthlyStats['operation_distribution'] as $operation)
                    @php
                        $pct = $monthlyStats['total_cost'] > 0
                            ? ($operation['total_cost'] / $monthlyStats['total_cost']) * 100
                            : 0;
                    @endphp
                    <div class="@if(!$loop->last) mb-4 @endif">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold">{{ ucfirst($operation['operation_type']) }}</span>
                            </div>
                            <div>
                                <strong class="me-3">${{ number_format($operation['total_cost'], 4) }}</strong>
                                <span class="fs-2 text-muted me-2">{{ number_format($operation['requests']) }} solicitudes</span>
                                <span class="fs-2 text-muted">· {{ number_format($operation['total_tokens']) }} tokens</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-3">
                    <p class="text-muted mb-0">Sin actividad este mes</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Budget cards --}}
    @php
        $providers = [
            'openai'    => ['label' => 'OpenAI'],
            'anthropic' => ['label' => 'Anthropic'],
        ];
    @endphp
    <div class="row g-4 mb-4">
        @foreach($providers as $providerKey => $meta)
            @php
                $b = $budgets->get($providerKey);
                $pct = $b ? $b->usagePercentage() : 0;
                $usage = $b ? $b->currentMonthUsage() : 0;
            @endphp
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div>
                                    <h5 class="card-title fw-semibold mb-0">Presupuesto {{ $meta['label'] }}</h5>
                                    <p class="card-subtitle text-muted mb-0" style="font-size:0.78rem;">Límites y alertas mensuales</p>
                                </div>
                            </div>
                            @if($b && $b->is_active)
                                <span class="badge rounded-pill text-success" style="background:rgba(19,222,185,0.12);">Activo</span>
                            @else
                                <span class="badge rounded-pill bg-light text-muted">Inactivo</span>
                            @endif
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-4 ">
                                <p class="text-muted mb-1" >Consumido</p>
                                <p class="fw-semibold mb-0">${{ number_format($usage, 2) }}</p>
                            </div>
                            <div class="col-4  border-start border-end">
                                <p class="text-muted mb-1" >Límite mensual</p>
                                <p class="fw-semibold mb-0">${{ number_format($b?->monthly_limit ?? 0, 2) }}</p>
                            </div>
                            <div class="col-4 ">
                                <p class="text-muted mb-1" >Alerta al</p>
                                <p class="fw-semibold mb-0">{{ $b?->alert_threshold_pct ?? '—' }}%</p>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-1 d-flex justify-content-between" >
                            <span class="text-muted">{{ number_format($pct, 1) }}% utilizado</span>
                            @if($b?->daily_limit)
                                <span class="text-muted">Límite diario: ${{ number_format($b->daily_limit, 2) }}</span>
                            @endif
                        </div>
                        <div style="height:5px;background:#f0f0f0;border-radius:3px;">
                            <div style="height:5px;width:{{ min($pct, 100) }}%;background:#13deb9;border-radius:3px;transition:width .3s;"></div>
                        </div>

                        @if($b?->block_on_exceed)
                            <p class="text-muted mt-2 mb-0" >
                                <i class="fas fa-lock me-1"></i> Bloquea solicitudes al superar el límite
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Metricas de contenido IA --}}
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="mb-4">
                <h5 class="card-title fw-semibold mb-1">Metricas de contenido IA</h5>
                <p class="card-subtitle text-muted mb-0">Resumen de generacion y gasto IA</p>
            </div>
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="card-title mb-0 small">Contenidos generados</h6>
                                <span class="kpi-icon-dyn" style="background: #90bb1320; color: #90bb13;">
                                    <i class="fas fa-robot"></i>
                                </span>
                            </div>
                            <h4 class="mb-0 fw-bold" style="color: #90bb13;">{{ number_format($contentMetrics['generated_count']) }}</h4>
                            <small class="text-muted">pend. validacion + validados + publicados</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="card-title mb-0 small">Sin fuentes web</h6>
                                <span class="kpi-icon-dyn" style="background: #fd7e1420; color: #fd7e14;">
                                    <i class="fas fa-unlink"></i>
                                </span>
                            </div>
                            <h4 class="mb-0 fw-bold {{ $contentMetrics['no_sources_count'] === 0 ? 'text-muted' : '' }}" style="{{ $contentMetrics['no_sources_count'] > 0 ? 'color:#fd7e14;' : '' }}">
                                {{ number_format($contentMetrics['no_sources_count']) }}
                            </h4>
                            @if($contentMetrics['generated_count'] > 0)
                                <small class="text-muted">{{ number_format($contentMetrics['no_sources_count'] / $contentMetrics['generated_count'] * 100, 1) }}% del total generado</small>
                            @else
                                <small class="text-muted">sin sources_used</small>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="card-title mb-0 small">Gasto IA del mes</h6>
                                <span class="kpi-icon-dyn" style="background: #90bb1320; color: #90bb13;">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                            </div>
                            <h4 class="mb-0 fw-bold" style="color: #90bb13;">${{ number_format($contentMetrics['monthly_cost'], 4) }}</h4>
                            <small class="text-muted">{{ now()->translatedFormat('F Y') }}</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="card-title mb-0 small">Gasto IA hoy</h6>
                                <span class="kpi-icon-dyn" style="background: #5a6a8520; color: #5a6a85;">
                                    <i class="fas fa-calendar-day"></i>
                                </span>
                            </div>
                            <h4 class="mb-0 fw-bold" style="color: #5a6a85;">${{ number_format($contentMetrics['daily_cost'], 4) }}</h4>
                            <small class="text-muted">{{ now()->translatedFormat('d/m/Y') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent logs --}}
    <div class="card mb-4">
        <div class="card-body p-4 pb-0">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="card-title fw-semibold mb-1">Actividad reciente</h5>
                    <p class="card-subtitle text-muted mb-0">Ultimas llamadas a proveedores de IA</p>
                </div>
                <a href="{{ route('settings.suppliers.monitoring.logs') }}" class="btn btn-sm btn-outline-secondary">
                    Ver todos
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($recentLogs->isNotEmpty())
                <div class="table-responsive" style="max-height: 560px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="position: sticky; top: 0; background: #fff; z-index: 1;">
                            <tr>
                                <th class="ps-4" style="color: #5a6a85;">Modelo</th>
                                <th style="color: #5a6a85;">Operacion</th>
                                <th style="color: #5a6a85;">Proveedor</th>
                                <th style="color: #5a6a85;">Tokens</th>
                                <th style="color: #5a6a85;">Costo</th>
                                <th style="color: #5a6a85;">Latencia</th>
                                <th style="color: #5a6a85;">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentLogs as $log)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fs-3 fw-semibold">{{ $log->model }}</span>
                                    </td>
                                    <td>
                                        <span class="rounded-2 px-2 py-1 fs-2 fw-semibold bg-light" >
                                            {{ ucfirst($log->operation_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fs-3 text-muted">{{ $log->supplier?->label ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="fs-3">{{ number_format($log->total_tokens) }}</span>
                                        <span class="fs-2 text-muted ms-1">({{ number_format($log->input_tokens) }} / {{ number_format($log->output_tokens) }})</span>
                                    </td>
                                    <td>
                                        <span class="fs-3 fw-semibold">${{ number_format($log->total_cost, 6) }}</span>
                                    </td>
                                    <td>
                                        @if($log->latency_ms)
                                            <span class="fs-3 fw-semibold">{{ number_format($log->latency_ms) }}ms</span>
                                        @else
                                            <span class="fs-3 text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fs-3 text-muted" title="{{ $log->created_at->format('d/m/Y H:i:s') }}">{{ $log->created_at->diffForHumans() }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <p class="text-muted mb-0">No hay actividad de IA registrada</p>
                </div>
            @endif
        </div>
        @if($recentLogs->isNotEmpty())
            <div class="card-body border-top pt-3 pb-3">
                <span class="fs-2 text-muted">
                    Mostrando {{ $recentLogs->count() }} registros mas recientes
                </span>
            </div>
        @endif
    </div>
</div>

{{-- Modal: configurar presupuestos (unificado) --}}
@can('suppliers.monitoring.manage')
<div class="modal fade" id="budget-config-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header pb-2">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Configurar presupuestos</h5>
                    <p class="text-muted mb-0" style="font-size:0.82rem;">OpenAI y Anthropic en un solo formulario</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form action="{{ route('settings.suppliers.monitoring.budgets.update') }}" method="POST">
                @csrf

                <div class="modal-body">
                    <div class="row g-4">

                        @foreach(['openai' => 'OpenAI', 'anthropic' => 'Anthropic'] as $prov => $provLabel)
                            @php $b = $budgets->get($prov); @endphp
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 h-100" style="border:1px solid #e9ecef;">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="fw-semibold">{{ $provLabel }}</span>
                                        @if($b && $b->is_active)
                                            <span class="badge rounded-pill text-success" style="background:rgba(19,222,185,0.12);font-size:0.7rem;">Activo</span>
                                        @else
                                            <span class="badge rounded-pill bg-light text-muted" style="font-size:0.7rem;">Inactivo</span>
                                        @endif
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Límite mensual (USD)</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" min="0"
                                                   name="budgets[{{ $prov }}][monthly_limit]"
                                                   class="form-control @error('budgets.'.$prov.'.monthly_limit') is-invalid @enderror"
                                                   value="{{ old('budgets.'.$prov.'.monthly_limit', $b?->monthly_limit ?? 0) }}"
                                                   required>
                                        </div>
                                        @error('budgets.'.$prov.'.monthly_limit')
                                            <div class="text-danger" >{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Límite diario (USD) <span class="text-muted fw-normal">— opcional</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" min="0"
                                                   name="budgets[{{ $prov }}][daily_limit]"
                                                   class="form-control"
                                                   value="{{ old('budgets.'.$prov.'.daily_limit', $b?->daily_limit) }}"
                                                   placeholder="Sin límite diario">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Alertar al alcanzar (%)</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="1" min="0" max="100"
                                                   name="budgets[{{ $prov }}][alert_threshold_pct]"
                                                   class="form-control @error('budgets.'.$prov.'.alert_threshold_pct') is-invalid @enderror"
                                                   value="{{ old('budgets.'.$prov.'.alert_threshold_pct', $b?->alert_threshold_pct ?? 80) }}"
                                                   required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                   name="budgets[{{ $prov }}][is_active]"
                                                   id="modal_active_{{ $prov }}" value="1"
                                                   {{ ($b?->is_active ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="modal_active_{{ $prov }}" style="font-size:0.82rem;">Activo</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                   name="budgets[{{ $prov }}][block_on_exceed]"
                                                   id="modal_block_{{ $prov }}" value="1"
                                                   {{ ($b?->block_on_exceed ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="modal_block_{{ $prov }}" style="font-size:0.82rem;">Bloquear al superar</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                       Guardar presupuestos
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endcan

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function () {
    let usageChart = null;
    let modelChart = null;
    let byModel = false;

    const modelColors = [
        { border: '#2a3547', bg: 'rgba(42,  53,  71,  0.15)' },
        { border: '#5a6a85', bg: 'rgba(90,  106, 133, 0.15)' },
        { border: '#8a96a8', bg: 'rgba(138, 150, 168, 0.15)' },
        { border: '#3f4f6a', bg: 'rgba(63,  79,  106, 0.15)' },
        { border: '#6d7f95', bg: 'rgba(109, 127, 149, 0.15)' },
        { border: '#b0bac8', bg: 'rgba(176, 186, 200, 0.15)' },
    ];

    loadChartData(7);
    initModelChart();
    initSparklines();

    $('.period-btn').on('click', function () {
        $('.period-btn').removeClass('active');
        $(this).addClass('active');
        loadChartData($(this).data('period'));
    });

    $('#byModelToggle').on('change', function () {
        byModel = $(this).is(':checked');
        loadChartData($('.period-btn.active').data('period'));
    });

    function loadChartData(days) {
        $.ajax({
            url: '{{ route("settings.suppliers.monitoring.usage-stats") }}',
            method: 'GET',
            data: { days: days, by_model: byModel ? 1 : 0 },
            success: function (response) {
                if (response.success) renderLineChart(response);
            },
            error: function () {
                if (typeof toastr !== 'undefined') toastr.error('Error al cargar los datos del grafico');
            }
        });
    }

    function renderLineChart(response) {
        const ctx = document.getElementById('usageChart').getContext('2d');
        if (usageChart) usageChart.destroy();

        const entries = Object.entries(response.datasets);
        const datasets = entries.map(function ([key, dataset], idx) {
            const colors = modelColors[idx % modelColors.length];
            return {
                label: dataset.label,
                data: dataset.data,
                borderColor: colors.border,
                backgroundColor: colors.bg,
                borderWidth: 2,
                tension: 0.4,
                fill: entries.length === 1,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: colors.border,
            };
        });

        const labels = response.labels.map(function (date) {
            const parts = date.split('-');
            return parts[2] + '/' + parts[1];
        });

        usageChart = new Chart(ctx, {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: entries.length > 1,
                        position: 'top',
                        labels: { boxWidth: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#2a3547',
                        titleFont: { size: 12 },
                        bodyFont: { size: 11 },
                        cornerRadius: 8,
                        padding: 10,
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': $' + context.parsed.y.toFixed(6);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        ticks: { callback: function (v) { return '$' + v.toFixed(4); }, font: { size: 11 }, color: '#5a6a85' },
                        grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false }
                    },
                    x: {
                        border: { display: false },
                        ticks: { font: { size: 10 }, color: '#5a6a85', maxRotation: 45 },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function initModelChart() {
        const canvas = document.getElementById('modelChart');
        if (!canvas) return;

        const distribution = @json($monthlyStats['model_distribution']);
        const labels = [];
        const data = [];
        const colors = [];

        let idx = 0;
        Object.entries(distribution).forEach(function ([key, item]) {
            labels.push(item.label);
            data.push(item.cost);
            colors.push(modelColors[idx % modelColors.length].border);
            idx++;
        });

        modelChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 }, color: '#5a6a85' }
                    },
                    tooltip: {
                        backgroundColor: '#2a3547',
                        cornerRadius: 8,
                        padding: 10,
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                const pct = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': $' + context.parsed.toFixed(6) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // ── Sparklines de las KPI cards principales (últimos 7 días) ───────────
    function initSparklines() {
        const series = @json($sparklines);
        if (!series || !series.labels || !series.labels.length) return;

        const sparkOpts = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false },
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
                x: { display: false },
                y: { display: false, beginAtZero: true },
            },
            elements: { point: { radius: 0 } },
        };

        const makeSpark = function (canvasId, data) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: series.labels,
                    datasets: [{
                        data: data,
                        borderColor: '#90bb13',
                        backgroundColor: 'rgba(144, 187, 19, 0.12)',
                        borderWidth: 1.5,
                        tension: 0.4,
                        fill: true,
                    }],
                },
                options: sparkOpts,
            });
        };

        makeSpark('spark-cost', series.cost);
        makeSpark('spark-requests', series.requests);
        makeSpark('spark-tokens', series.tokens);
        makeSpark('spark-latency', series.latency);
    }

    setInterval(function () {
        loadChartData($('.period-btn.active').data('period'));
    }, 300000);

    // ── Alertas de presupuesto ────────────────────────────────────────────────
    const alertLevelClass = { danger: 'alert-danger', warning: 'alert-warning' };
    const alertLevelIcon  = { danger: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation' };

    function loadBudgetAlerts() {
        $.getJSON('{{ route("settings.suppliers.monitoring.budget-alerts") }}', function (data) {
            if (!data.success || !data.count) {
                $('#budget-alerts-banner').hide().empty();
                return;
            }
            const html = data.alerts.map(function (a) {
                const cls  = alertLevelClass[a.level]  || 'alert-warning';
                const icon = alertLevelIcon[a.level] || 'fa-triangle-exclamation';
                return `<div class="alert ${cls} d-flex align-items-center gap-2 py-2 mb-1">
                    <i class="fas ${icon}"></i>
                    <span>${a.message}</span>
                </div>`;
            }).join('');
            $('#budget-alerts-banner').html(html).show();
        });
    }

    loadBudgetAlerts();
    setInterval(loadBudgetAlerts, 120000);
});
</script>
@endpush
