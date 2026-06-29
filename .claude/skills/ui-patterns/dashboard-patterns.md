# Dashboard Patterns

## Full Dashboard Template

```blade
@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

    @include('core::components.card', ['title' => $pageTitle])

    {{-- ========== KPI STATS ROW ========== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center"
                             style="width:48px;height:48px">
                            <i class="fas fa-chart-line text-primary fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Total</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['total']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center"
                             style="width:48px;height:48px">
                            <i class="fas fa-check-circle text-success fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Completados</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['completed']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center"
                             style="width:48px;height:48px">
                            <i class="fas fa-hourglass-half text-warning fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Pendientes</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['pending']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center"
                             style="width:48px;height:48px">
                            <i class="fas fa-exclamation-circle text-danger fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Alertas</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['alerts']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== CHART + SIDEBAR ========== --}}
    <div class="row g-3">

        {{-- Main chart --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">Tendencia</h6>
                        <select id="chart-range" class="form-select form-select-sm" style="width:auto">
                            <option value="7">7 dias</option>
                            <option value="30" selected>30 dias</option>
                            <option value="90">90 dias</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div id="trend-chart" style="height:350px"></div>
                </div>
            </div>
        </div>

        {{-- Side stats / pie chart --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="fw-bold mb-0">Distribucion por categoria</h6>
                </div>
                <div class="card-body">
                    <div id="category-chart" style="height:350px"></div>
                </div>
            </div>
        </div>

    </div>

    {{-- ========== RECENT TABLE ========== --}}
    <div class="card mt-3">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Registros recientes</h6>
                <a href="{{ route('resource.index') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="text-center">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent as $item)
                            <tr>
                                <td class="small fw-semibold">{{ $item->name }}</td>
                                <td><span class="badge bg-success-subtle text-success">{{ $item->status }}</span></td>
                                <td class="small text-muted">{{ $item->created_at->diffForHumans() }}</td>
                                <td class="text-center">
                                    <a href="{{ route('resource.show', $item) }}" class="text-primary">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    let trendChart = null;
    let categoryChart = null;

    function loadTrendChart(days) {
        $.getJSON('{{ route('module.dashboard.trend-data') }}', { days: days }, function (res) {
            if (trendChart) trendChart.dispose();

            trendChart = $('#trend-chart').dxChart({
                dataSource: res.labels.map((label, i) => ({
                    date: label,
                    total: res.datasets[0].data[i]
                })),
                argumentAxis: { argumentType: 'string' },
                series: [{
                    valueField: 'total',
                    argumentField: 'date',
                    name: 'Total',
                    type: 'bar',
                    color: '#90bb13'
                }],
                tooltip: { enabled: true },
                legend: { visible: false }
            }).dxChart('instance');
        });
    }

    function loadCategoryChart() {
        $.getJSON('{{ route('module.dashboard.category-data') }}', function (res) {
            if (categoryChart) categoryChart.dispose();

            categoryChart = $('#category-chart').dxPieChart({
                dataSource: res.data,
                series: [{
                    argumentField: 'name',
                    valueField: 'value',
                    label: { visible: true, format: 'fixedPoint' }
                }],
                legend: {
                    visible: true,
                    orientation: 'horizontal',
                    verticalAlignment: 'bottom',
                    horizontalAlignment: 'center'
                }
            }).dxPieChart('instance');
        });
    }

    loadTrendChart(30);
    loadCategoryChart();

    $('#chart-range').on('change', function () {
        loadTrendChart($(this).val());
    });
});
</script>
@endpush
```

## KPI Card Pattern (con icono circular)

```blade
<div class="col-md-3">
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-{color}-subtle d-flex align-items-center justify-content-center"
                     style="width:48px;height:48px">
                    <i class="fas fa-{icon} text-{color} fs-5"></i>
                </div>
                <div>
                    <p class="text-muted mb-1 small">{Label}</p>
                    <h4 class="fw-bold mb-0">{{ number_format($value) }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>
```

## Colores Semanticos para KPIs

| Uso | Color |
|-----|-------|
| Total/Principal | `primary` |
| Exito/Completado | `success` |
| Pendiente/Advertencia | `warning` |
| Alerta/Critico | `danger` |
| Info/Secundario | `info` |

## DevExpress Charts (estandar del proyecto)

### Bar Chart
```javascript
$('#chart').dxChart({
    dataSource: data,
    series: [{
        valueField: 'total',
        argumentField: 'date',
        type: 'bar',
        color: '#90bb13'  // Primary color del proyecto
    }]
});
```

### Pie Chart
```javascript
$('#chart').dxPieChart({
    dataSource: data,
    series: [{
        argumentField: 'name',
        valueField: 'value',
        label: { visible: true }
    }]
});
```

### Line Chart
```javascript
$('#chart').dxChart({
    dataSource: data,
    series: [{
        valueField: 'total',
        argumentField: 'date',
        type: 'line',
        color: '#90bb13'
    }]
});
```

## Paleta de Colores del Proyecto

- **Primary**: `#90bb13` (casi todos los modulos)
- **Analytics**: paleta roja `#90bb13`, `#333333`, `#7b0000` (SOLO Analytics)
- **Success**: `#13C672`
- **Danger**: `#FA896B`
- **Warning**: `#FEC90F`

## Reglas de Dashboard

1. **KPI row**: 4 cards con `col-md-3`, icono circular a la izquierda, valor a la derecha
2. **Charts row**: grafica principal `col-lg-8` + sidebar `col-lg-4`
3. **Chart height**: `350px` estandar
4. **Range selector**: `form-select-sm` con opciones 7/30/90 dias
5. **AJAX loading**: usar `$.getJSON` con callback para cada chart
6. **Dispose charts**: antes de redibujar, llamar `.dispose()` del instance anterior
7. **Recent table**: al final, con link "Ver todos" al listado completo
8. **Numbers**: siempre `number_format()` para display
9. **DevExpress**: `dxChart` para barras/lineas, `dxPieChart` para distribucion
