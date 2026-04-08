@extends('layouts.theme')

@section('title', 'Analytics: ' . $form->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            {{-- Header --}}
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                        <span class="text-muted">Analytics — últimos 30 días</span>
                    </div>
                </div>
                <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-list me-1"></i> Ver submissions
                </a>
            </div>

            {{-- Stats cards --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-3 fw-bold text-primary">{{ $analytics['total_submissions'] }}</div>
                            <div class="text-muted">Total submissions</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-3 fw-bold text-success">{{ number_format($analytics['conversion_rate'], 1) }}%</div>
                            <div class="text-muted">Tasa de conversión</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            @php $avgMin = $analytics['avg_time_to_complete'] ? gmdate('i:s', $analytics['avg_time_to_complete']) : '—'; @endphp
                            <div class="fs-3 fw-bold text-info">{{ $avgMin }}</div>
                            <div class="text-muted">Tiempo promedio</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-3 fw-bold text-warning">{{ $analytics['unread_count'] }}</div>
                            <div class="text-muted">No leídas</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Gráfico por día + dona estado --}}
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Submissions por día (últimos 30 días)</h6>
                            <div id="chartByDay" style="height: 260px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Distribución por estado</h6>
                            <div id="chartByStatus" style="height: 260px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Gráfico por hora --}}
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Submissions por hora del día</h6>
                            <div id="chartByHour" style="height: 220px;"></div>
                        </div>
                    </div>
                </div>

                {{-- Top UTM sources --}}
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Top fuentes de tráfico</h6>
                            @forelse ($analytics['top_utm_sources'] as $source)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-truncate">{{ $source['source'] ?: 'Directo' }}</span>
                                    <span class="badge bg-secondary ms-2">{{ $source['count'] }}</span>
                                </div>
                            @empty
                                <p class="text-muted">Sin datos de fuentes UTM.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Por país --}}
            @if (!empty($analytics['by_country']))
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Por país</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>País</th>
                                                <th class="text-end">Submissions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($analytics['by_country'] as $row)
                                                <tr>
                                                    <td class="small">{{ $row['country'] ?: 'Desconocido' }}</td>
                                                    <td class="text-end small">{{ $row['count'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const analyticsData = @json($analytics);

    // Submissions por día
    $('#chartByDay').dxChart({
        dataSource: analyticsData.submissions_by_day,
        series: [{
            valueField: 'count',
            argumentField: 'date',
            name: 'Submissions',
            type: 'line',
            color: '#90bb13',
        }],
        argumentAxis: { argumentType: 'string' },
        valueAxis: { allowDecimals: false },
        legend: { visible: false },
        tooltip: { enabled: true, format: '{value} submissions' },
    });

    // Distribución por estado
    const statusLabels = { new: 'Nuevo', in_review: 'En revisión', resolved: 'Resuelto', rejected: 'Rechazado' };
    const statusColors = { new: '#0d6efd', in_review: '#FEC90F', resolved: '#13C672', rejected: '#FA896B' };
    const statusData = Object.entries(analyticsData.submissions_by_status).map(([key, value]) => ({
        label: statusLabels[key] || key,
        value: value,
        color: statusColors[key] || '#6c757d',
    }));

    $('#chartByStatus').dxPieChart({
        dataSource: statusData,
        series: [{
            argumentField: 'label',
            valueField: 'value',
            label: { visible: true, format: '{value}' },
        }],
        customizePoint: function (point) {
            const item = statusData.find(d => d.label === point.argument);
            return item ? { color: item.color } : {};
        },
        legend: { visible: true, horizontalAlignment: 'center', verticalAlignment: 'bottom' },
        tooltip: { enabled: true },
    });

    // Submissions por hora
    $('#chartByHour').dxChart({
        dataSource: analyticsData.submissions_by_hour,
        series: [{
            valueField: 'count',
            argumentField: 'hour',
            name: 'Submissions',
            type: 'bar',
            color: '#90bb13',
        }],
        argumentAxis: { label: { customizeText: function (e) { return e.value + 'h'; } } },
        valueAxis: { allowDecimals: false },
        legend: { visible: false },
        tooltip: { enabled: true, customizeTooltip: function (info) { return { text: info.argument + ':00 h — ' + info.value + ' envíos' }; } },
    });
</script>
@endpush
