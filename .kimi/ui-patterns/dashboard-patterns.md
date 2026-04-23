# Dashboard Patterns

## Full Dashboard Template

```blade
@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

    @include('core::components.card', ['title' => $pageTitle])

    {{-- ========== KPI STATS ROW ========== --}}
    <div class="row g-3 mb-4" id="dashboard-kpis">
        <div class="col-lg-3 col-md-6">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Total</h5>
                            <h4 class="fw-semibold mb-2" id="kpi-total">
                                {{ number_format($stats['total']) }}
                            </h4>
                            <p class="fs-3 mb-0 text-muted">Registros totales</p>
                        </div>
                        <div class="col-4">
                            <div class="d-flex justify-content-end">
                                <span class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center"
                                      style="width:44px;height:44px;">
                                    <i class="fas fa-chart-line text-primary"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Completados</h5>
                            <h4 class="fw-semibold mb-2" id="kpi-completed">
                                {{ number_format($stats['completed']) }}
                            </h4>
                            <p class="fs-3 mb-0 text-muted">Este mes</p>
                        </div>
                        <div class="col-4">
                            <div class="d-flex justify-content-end">
                                <span class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center"
                                      style="width:44px;height:44px;">
                                    <i class="fas fa-check-circle text-success"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Pendientes</h5>
                            <h4 class="fw-semibold mb-2" id="kpi-pending">
                                {{ number_format($stats['pending']) }}
                            </h4>
                            <p class="fs-3 mb-0 text-muted">En cola</p>
                        </div>
                        <div class="col-4">
                            <div class="d-flex justify-content-end">
                                <span class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center"
                                      style="width:44px;height:44px;">
                                    <i class="fas fa-hourglass-half text-warning"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Alertas</h5>
                            <h4 class="fw-semibold mb-2" id="kpi-alerts">
                                {{ number_format($stats['alerts']) }}
                            </h4>
                            <p class="fs-3 mb-0 text-muted">Requieren atencion</p>
                        </div>
                        <div class="col-4">
                            <div class="d-flex justify-content-end">
                                <span class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center"
                                      style="width:44px;height:44px;">
                                    <i class="fas fa-exclamation-circle text-danger"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== CHART + SIDEBAR ========== --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card w-100">
                <div class="card-header d-md-flex align-items-center">
                    <div>
                        <h4 class="card-title fw-semibold mb-0">Tendencia</h4>
                        <p class="card-subtitle mt-1">Evolucion temporal</p>
                    </div>
                    <div class="ms-auto mt-3 mt-md-0">
                        <select id="chart-range" class="form-select form-select-sm">
                            <option value="7">7 dias</option>
                            <option value="30" selected>30 dias</option>
                            <option value="90">90 dias</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div id="trend-chart" style="height:300px;">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="spinner-border spinner-border-sm text-secondary"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== DISTRIBUTION + RECENT LIST ========== --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card w-100 h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Distribucion</h4>
                    <p class="card-subtitle mt-1">Por categoria</p>
                </div>
                <div class="card-body">
                    <div id="distribution-chart" style="height:260px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card w-100 h-100">
                <div class="card-header d-md-flex align-items-center">
                    <div>
                        <h4 class="card-title fw-semibold mb-0">Registros recientes</h4>
                        <p class="card-subtitle mt-1">Ultimas acciones</p>
                    </div>
                    <div class="ms-auto mt-3 mt-md-0">
                        <a href="{{ route('resource.index') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="recent-list">
                        {{-- Loaded via AJAX --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
(function () {
    'use strict';

    let trendChart = null;
    let distChart = null;
    const fmt = n => new Intl.NumberFormat('es-ES').format(n);
    const emptyState = msg => `<div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i><small>${msg}</small></div>`;

    function loadTrendChart(days) {
        $.getJSON('{{ route("module.dashboard.trend-data") }}', { days: days }, function (data) {
            if (trendChart) { trendChart.destroy(); trendChart = null; }
            $('#trend-chart').html('');

            if (!data.labels || !data.labels.length) {
                $('#trend-chart').html(emptyState('Sin datos'));
                return;
            }

            trendChart = new ApexCharts(document.querySelector('#trend-chart'), {
                series: [{ name: 'Total', data: data.values || [] }],
                chart: {
                    type: 'area',
                    height: 295,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    fontFamily: 'inherit'
                },
                colors: ['#90bb13'],
                stroke: { curve: 'smooth', width: 2 },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] }
                },
                xaxis: {
                    categories: data.labels,
                    labels: { style: { fontSize: '11px', colors: '#adb5bd' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: { style: { fontSize: '11px', colors: '#adb5bd' }, formatter: v => Math.round(v) }
                },
                grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
                tooltip: { theme: 'light', shared: true, intersect: false },
                legend: { show: false },
                markers: { size: 0 }
            });
            trendChart.render();
        }).fail(function () {
            $('#trend-chart').html(emptyState('No se pudieron cargar los datos'));
        });
    }

    function loadDistributionChart() {
        $.getJSON('{{ route("module.dashboard.distribution-data") }}', function (data) {
            if (distChart) { distChart.destroy(); }
            $('#distribution-chart').html('');

            if (!data.labels || !data.labels.length) {
                $('#distribution-chart').html(emptyState('Sin datos'));
                return;
            }

            distChart = new ApexCharts(document.querySelector('#distribution-chart'), {
                series: data.series || [],
                labels: data.labels || [],
                chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
                colors: ['#90bb13', '#13C672', '#FEC90F', '#FA896B', '#333333'],
                legend: { show: false },
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: v => fmt(v) } },
                plotOptions: { pie: { donut: { size: '75%' } } }
            });
            distChart.render();
        }).fail(function () {
            $('#distribution-chart').html(emptyState('No se pudieron cargar los datos'));
        });
    }

    loadTrendChart(30);
    loadDistributionChart();

    $('#chart-range').on('change', function () {
        loadTrendChart(parseInt($(this).val()));
    });
}());
</script>
@endpush
```

## KPI Card Pattern

```blade
<div class="col-lg-3 col-md-6">
    <div class="card w-100">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-8">
                    <h5 class="card-title fw-semibold mb-3">{Label}</h5>
                    <h4 class="fw-semibold mb-2">{{ number_format($value) }}</h4>
                    <p class="fs-3 mb-0 text-muted">{Sublabel}</p>
                </div>
                <div class="col-4">
                    <div class="d-flex justify-content-end">
                        <span class="rounded-circle bg-{color}-subtle d-flex align-items-center justify-content-center"
                              style="width:44px;height:44px;">
                            <i class="fas fa-{icon} text-{color}"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

## KPI with Sparkline

```blade
<div class="col-lg-3 col-md-6">
    <div class="card w-100">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-8">
                    <h5 class="card-title fw-semibold mb-3">{Label}</h5>
                    <h4 class="fw-semibold mb-2 kpi-value" id="kpi-id">
                        {{ number_format($value) }}
                    </h4>
                    <div id="cmp-id">{comparison badge}</div>
                </div>
                <div class="col-4">
                    <div class="d-flex justify-content-center">
                        <div id="spark-id"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

Sparkline config:
```javascript
new ApexCharts(document.querySelector('#spark-id'), {
    series: [{ data: sparkData }],
    chart: { type: 'area', height: 70, width: 70, sparkline: { enabled: true }, animations: { enabled: false } },
    colors: ['#90bb13'],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
    tooltip: { fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: () => '' } } }
});
```

## Comparison Badge Pattern

```javascript
function cmpBadge(current, previous, invert) {
    if (!previous || previous === 0) return '';
    const pct = ((current - previous) / previous * 100).toFixed(1);
    const up = invert ? parseFloat(pct) < 0 : parseFloat(pct) > 0;
    const icon = up ? 'fa-arrow-up' : 'fa-arrow-down';
    const bg = up ? 'bg-success-subtle' : 'bg-danger-subtle';
    const txt = up ? 'text-success' : 'text-danger';
    const sign = parseFloat(pct) > 0 ? '+' : '';
    return `<div class="d-flex align-items-center">
        <span class="me-1 rounded-circle ${bg} d-flex align-items-center justify-content-center" style="width:20px;height:20px;">
            <i class="fas ${icon} ${txt}" style="font-size:0.6rem;"></i>
        </span>
        <p class="text-dark me-1 fs-3 mb-0">${sign}${Math.abs(pct)}%</p>
        <p class="fs-3 mb-0 text-muted">vs anterior</p>
    </div>`;
}
```

## RadialBar (Security Score, 2FA)

```javascript
new ApexCharts(document.querySelector('#radial-chart'), {
    series: [score],
    chart: { type: 'radialBar', height: 140, fontFamily: 'inherit' },
    plotOptions: {
        radialBar: {
            hollow: { size: '55%' },
            dataLabels: {
                show: true,
                name: { show: false },
                value: { fontSize: '22px', fontWeight: 600, color: color, formatter: v => v }
            }
        }
    },
    colors: [color],
    stroke: { lineCap: 'round' }
});
```

## Empty State Helper

```javascript
const emptyState = msg => `<div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i><small>${msg}</small></div>`;
```

## Skeleton Loading

```blade
@push('css')
<style>
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 4px;
}
@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.skeleton-text { height: 12px; }
.skeleton-title { height: 32px; width: 80px; }
.skeleton-circle { width: 36px; height: 36px; border-radius: 8px; }
</style>
@endpush
```

## Colores Semanticos para KPIs

| Uso | Color | Clases |
|-----|-------|--------|
| Total/Principal | `primary` | `bg-primary-subtle text-primary` |
| Exito/Completado | `success` | `bg-success-subtle text-success` |
| Pendiente/Advertencia | `warning` | `bg-warning-subtle text-warning` |
| Alerta/Critico | `danger` | `bg-danger-subtle text-danger` |
| Info/Secundario | `info` | `bg-info-subtle text-info` |

## Reglas de Dashboard

1. **KPI row**: 4 cards con `col-lg-3 col-md-6`, icono circular 44x44 a la derecha
2. **Chart principal**: `col-12`, height `300px`, toolbar y zoom desactivados
3. **Chart distribucion**: donut chart, height `200px`, `donut.size: '75%'`
4. **Range selector**: `form-select-sm` con opciones 7/30/90 dias
5. **AJAX loading**: skeletons o spinner en containers, luego reemplazar con chart
6. **Destroy antes de render**: siempre destruir instancia anterior antes de crear nueva
7. **Recent list**: al final, con link "Ver todos" al listado completo
8. **Numbers**: siempre `number_format()` o `Intl.NumberFormat('es-ES')` para display
9. **Font family**: usar `fontFamily: 'inherit'` en ApexCharts para heredar del tema
10. **Colors**: Primary `#90bb13`, Success `#13C672`, Danger `#FA896B`, Warning `#FEC90F`
