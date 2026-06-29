@extends('layouts.theme')

@section('title', 'Remarketing — Triggers de automation')

@section('content')

    {{-- Header bar --}}
    <div class="card card-body mb-4 border-0 shadow-sm">
        <div class="row align-items-center g-3">
            <div class="col-md">
                <h5 class="mb-0 fw-semibold">Triggers de automation</h5>
                <p class="mb-0 text-muted small">Log de ejecuciones y tasa de entrega de emails automatizados</p>
            </div>
            <div class="col-md-auto d-flex align-items-center gap-2">
                <form method="GET" action="{{ route('remarketing.triggers.index') }}" class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 small text-muted">Período:</label>
                    <select name="hours" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        @foreach([6 => '6h', 24 => '24h', 48 => '48h', 168 => '7 días', 720 => '30 días'] as $val => $label)
                            <option value="{{ $val }}" @selected($hours === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('remarketing.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </div>

    @include('core::components.alerts')

    {{-- KPI cards --}}
    <div class="row g-3 mb-4">

        <div class="col-md-6 col-xl-3">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Intentos 24h</h5>
                            <h4 class="fw-semibold mb-2">{{ number_format($totalAttempts24h) }}</h4>
                            <p class="fs-3 mb-0 text-muted">Triggers ejecutados</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-bolt text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Enviados 24h</h5>
                            <h4 class="fw-semibold mb-2">{{ number_format($sentLast24h) }}</h4>
                            <p class="fs-3 mb-0 text-muted">Emails entregados</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-paper-plane text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Tasa de envío</h5>
                            <h4 class="fw-semibold mb-2">{{ $deliveryRate }}%</h4>
                            <p class="fs-3 mb-0 text-muted">Sobre intentos 24h</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-chart-pie text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Tipos activos</h5>
                            <h4 class="fw-semibold mb-2">{{ count($stats) }}</h4>
                            <p class="fs-3 mb-0 text-muted">En el período</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-layer-group text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Stats por trigger_type + Hourly chart --}}
    <div class="row g-3 mb-4">

        {{-- By type table --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title fw-semibold mb-0">Por tipo de trigger</h4>
                        <p class="card-subtitle mt-1">Últimas {{ $hours }}h</p>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($stats->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                            <small>Sin datos en este período</small>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Enviados</th>
                                        <th class="text-end">Saltados</th>
                                        <th class="text-end">Clientes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stats as $row)
                                    <tr>
                                        <td><code class="small">{{ $row->trigger_type }}</code></td>
                                        <td class="text-end small">{{ number_format($row->total) }}</td>
                                        <td class="text-end small text-success fw-semibold">{{ number_format($row->sent) }}</td>
                                        <td class="text-end small text-warning">{{ number_format($row->skipped) }}</td>
                                        <td class="text-end small">{{ number_format($row->unique_customers) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Hourly chart --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Distribución horaria (últimas 24h)</h4>
                    <p class="card-subtitle mt-1">Total de intentos vs enviados por hora</p>
                </div>
                <div class="card-body">
                    <div id="hourly-chart" style="height:260px"></div>
                </div>
            </div>
        </div>

    </div>

    {{-- Recent triggers log --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="card-title fw-semibold mb-0">Ejecuciones recientes</h4>
                <p class="card-subtitle mt-1">Últimas 100 del período seleccionado</p>
            </div>
        </div>
        <div class="card-body p-0">
            @if($recent->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                    <small>Sin ejecuciones en este período</small>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent as $r)
                            <tr>
                                <td class="small text-muted text-nowrap">
                                    {{ \Illuminate\Support\Carbon::parse($r->triggered_at)->diffForHumans() }}
                                </td>
                                <td><code class="small">{{ $r->trigger_type }}</code></td>
                                <td class="small">{{ $r->customer_email ?? '—' }}</td>
                                <td>
                                    @if($r->email_sent)
                                        <span class="badge bg-success-subtle text-success">Enviado</span>
                                    @elseif($r->skip_reason)
                                        <span class="badge bg-warning-subtle text-warning" title="{{ $r->skip_reason }}">
                                            {{ Str::limit($r->skip_reason, 30) }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('css')
<style>
    .stat-icon { width: 44px; height: 44px; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    var chartData = @json($hourlyChart);

    if (chartData.length === 0) {
        $('#hourly-chart').html('<div class="d-flex align-items-center justify-content-center h-100 text-muted"><small>Sin datos en las últimas 24h</small></div>');
        return;
    }

    var dataSource = chartData.map(function (d) {
        var hour = d.hour ? d.hour.split(' ')[1] : d.hour;
        return { hour: hour, total: d.total, sent: d.sent };
    });

    $('#hourly-chart').dxChart({
        dataSource: dataSource,
        series: [
            {
                valueField: 'total',
                argumentField: 'hour',
                name: 'Total',
                type: 'line',
                color: '#3b82f6',
                point: { visible: false }
            },
            {
                valueField: 'sent',
                argumentField: 'hour',
                name: 'Enviados',
                type: 'line',
                color: '#10b981',
                point: { visible: false }
            }
        ],
        argumentAxis: { argumentType: 'string' },
        valueAxis: { allowDecimals: false },
        tooltip: { enabled: true, shared: true },
        legend: { visible: true, position: 'outside', horizontalAlignment: 'right' }
    });
});
</script>
@endpush
