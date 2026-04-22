@extends('layouts.theme')

@section('title', 'Analytics: ' . $form->name)

@push('css')
<style>
    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    .kpi-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .kpi-icon-box-red  { background: #fce8e8; color: #b10100; }
    .kpi-icon-box-mid  { background: #f0e0e0; color: #c41c1c; }
    .chart-260 { height: 260px; }
    .chart-220 { height: 220px; }
    .chart-200 { height: 200px; }
    .status-pct { min-width: 38px; }
</style>
@endpush

@section('content')

    @include('core::components.card', ['title' => 'Analíticas'])

    <div class="widget-content searchable-container list">

        <div class="card mb-4">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('settings.forms.edit', $form) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                            <small class="text-muted">{{ $form->slug }}</small>
                        </div>
                        @if ($form->is_active)
                            <span class="badge bg-light-success text-success">Activo</span>
                        @else
                            <span class="badge bg-light-danger text-danger">Inactivo</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-desktop me-1"></i> Preview
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-inbox me-1"></i> Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="row mb-4 g-3">
            <div class="col-md-4">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Total submissions</h5>
                                <h4 class="fw-semibold mb-0">{{ $totalSubmissions }}</h4>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center">
                                    <div id="spark-submissions"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Tasa de conversión</h5>
                                <h4 class="fw-semibold mb-0">{{ number_format($conversionRate, 1) }}%</h4>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="kpi-icon-box kpi-icon-box-red">
                                        <i class="fas fa-chart-pie"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Abandonos</h5>
                                <h4 class="fw-semibold mb-0">{{ $abandonCount }}</h4>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="kpi-icon-box kpi-icon-box-mid">
                                        <i class="fas fa-door-open"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-9">

                {{-- Day chart --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Submissions por día</h4>
                        <p class="card-subtitle mt-1">Últimos 30 días</p>
                    </div>
                    <div class="card-body">
                        <div id="chartByDay" class="chart-260"></div>
                    </div>
                </div>

                {{-- Hour chart --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Submissions por hora del día</h4>
                        <p class="card-subtitle mt-1">Distribución horaria</p>
                    </div>
                    <div class="card-body">
                        <div id="chartByHour" class="chart-220"></div>
                    </div>
                </div>

                {{-- Status + Resumen row --}}
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title fw-semibold mb-0">Distribución por estado</h4>
                                <p class="card-subtitle mt-1">Por submissions</p>
                            </div>
                            <div class="card-body">
                                <div id="chartByStatus" class="mb-3 chart-200"></div>
                                <hr class="my-3">
                                <div id="statusList"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title fw-semibold mb-0">Resumen</h4>
                                <p class="card-subtitle mt-1">Últimos 30 días</p>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-muted small">Total enviados</span>
                                    <span class="fw-semibold">{{ $totalSubmissions }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-muted small">Abandonos</span>
                                    <span class="fw-semibold">{{ $abandonCount }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-2">
                                    <span class="text-muted small">Conversión</span>
                                    <span class="fw-semibold">{{ number_format($conversionRate, 1) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(isset($topSourcePages) && $topSourcePages->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Top páginas que convierten</h6>
                        <small class="text-muted">Pages del sitio que más generan envíos a este formulario</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Página</th>
                                        <th class="text-end">Envíos</th>
                                        <th class="text-end">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalFromPages = $topSourcePages->sum('submissions_count'); @endphp
                                    @foreach($topSourcePages as $p)
                                        @php $pct = $totalFromPages > 0 ? round($p->submissions_count / $totalFromPages * 100, 1) : 0; @endphp
                                        <tr>
                                            <td><strong>{{ $p->title }}</strong> <small class="text-muted">/{{ $p->slug }}</small></td>
                                            <td class="text-end">{{ $p->submissions_count }}</td>
                                            <td class="text-end"><span class="badge bg-light text-dark">{{ $pct }}%</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

            </div>

            <div class="col-lg-3">
                @include('forms::settings.partials.tabs', ['active' => 'analytics'])
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    var submissionsByDay    = @json($submissionsByDay);
    var submissionsByStatus = @json($submissionsByStatus);
    var submissionsByHour   = @json($submissionsByHour);

    var statusLabels = { new: 'Nuevo', in_review: 'En revisión', resolved: 'Resuelto', rejected: 'Rechazado' };
    var statusColors = { new: '#0d6efd', in_review: '#FEC90F', resolved: '#13C672', rejected: '#FA896B' };

    var NO_DATA_TEXT = 'Sin datos disponibles';

    // ── Sparkline ──────────────────────────────────────────────────────────
    var sparkData = submissionsByDay.map(function (d) { return { val: parseInt(d.count) }; });
    if (sparkData.length > 0) {
        $('#spark-submissions').dxSparkline({
            dataSource: sparkData,
            type: 'line',
            valueField: 'val',
            showMinMax: false,
            showFirstLast: false,
            lineColor: '#b10100',
            lineWidth: 2,
            size: { width: 80, height: 40 },
        });
    } else {
        $('#spark-submissions').html('<span class="text-muted small">—</span>');
    }

    // ── Submissions por día ────────────────────────────────────────────────
    var dayData = submissionsByDay.map(function (d) {
        return { date: d.date, count: parseInt(d.count) };
    });
    $('#chartByDay').dxChart({
        dataSource: dayData,
        noDataText: NO_DATA_TEXT,
        series: [{
            valueField: 'count',
            argumentField: 'date',
            name: 'Submissions',
            type: 'line',
            color: '#b10100',
            point: { visible: dayData.length <= 10 },
            label: {
                visible: dayData.length > 0 && dayData.length <= 10,
                backgroundColor: '#b10100',
                font: { color: '#fff', size: 11 },
            },
        }],
        argumentAxis: { argumentType: 'string' },
        valueAxis: { allowDecimals: false, min: 0 },
        legend: { visible: false },
        tooltip: {
            enabled: true,
            customizeTooltip: function (info) {
                return { text: info.argument + ': ' + info.value + ' envío(s)' };
            },
        },
    });

    // ── Distribución por estado (donut) ────────────────────────────────────
    var statusData = submissionsByStatus.map(function (row) {
        return {
            label: statusLabels[row.status] || row.status,
            value: parseInt(row.count),
            color: statusColors[row.status] || '#6c757d',
        };
    });
    var totalForPercent = statusData.reduce(function (s, d) { return s + d.value; }, 0);

    $('#chartByStatus').dxPieChart({
        dataSource: statusData,
        type: 'doughnut',
        noDataText: NO_DATA_TEXT,
        series: [{
            argumentField: 'label',
            valueField: 'value',
            label: { visible: false },
        }],
        customizePoint: function (point) {
            var item = statusData.find(function (d) { return d.label === point.argument; });
            return item ? { color: item.color } : {};
        },
        legend: { visible: false },
        tooltip: {
            enabled: true,
            customizeTooltip: function (info) {
                var pct = totalForPercent > 0 ? ((info.value / totalForPercent) * 100).toFixed(1) : 0;
                return { text: info.argument + ': ' + info.value + ' (' + pct + '%)' };
            },
        },
    });

    // Status list below donut
    var listHtml = '';
    statusData.forEach(function (item) {
        var pct = totalForPercent > 0 ? ((item.value / totalForPercent) * 100).toFixed(1) : '0.0';
        listHtml += '<div class="d-flex align-items-center justify-content-between py-2 border-bottom">'
            + '<div class="d-flex align-items-center gap-2">'
            + '<span class="status-dot" style="background:' + item.color + ';"></span>'
            + '<span class="small">' + item.label + '</span>'
            + '</div>'
            + '<div class="d-flex align-items-center gap-3">'
            + '<span class="fw-semibold small">' + item.value + '</span>'
            + '<span class="text-muted small text-end status-pct">' + pct + '%</span>'
            + '</div>'
            + '</div>';
    });
    $('#statusList').html(listHtml || '<p class="text-muted small mb-0">Sin datos</p>');

    // ── Submissions por hora ───────────────────────────────────────────────
    // Fill all 24 hours so the axis is always complete
    var hourMap = {};
    submissionsByHour.forEach(function (d) { hourMap[parseInt(d.hour)] = parseInt(d.count); });
    var hourData = [];
    for (var h = 0; h < 24; h++) {
        hourData.push({ hour: h, count: hourMap[h] || 0 });
    }

    $('#chartByHour').dxChart({
        dataSource: hourData,
        noDataText: NO_DATA_TEXT,
        series: [{
            valueField: 'count',
            argumentField: 'hour',
            name: 'Submissions',
            type: 'bar',
            color: '#b10100',
            label: {
                visible: false,
            },
        }],
        argumentAxis: {
            label: { customizeText: function (e) { return e.value + 'h'; } },
        },
        valueAxis: { allowDecimals: false, min: 0 },
        legend: { visible: false },
        tooltip: {
            enabled: true,
            customizeTooltip: function (info) {
                return { text: info.argument + ':00 h — ' + info.value + ' envío(s)' };
            },
        },
    });
</script>
@endpush
