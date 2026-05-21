@extends('layouts.theme')

@section('title', 'Reportes')

@section('page_header')
    @include('core::components.card', [
        'title' => 'Reportes de rendimiento',
        'actions' => '<a href="' . route('remarketing.reports.export-pdf') . '" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-pdf me-1"></i> Exportar PDF
        </a>',
    ])
@endsection

@section('content')

    @include('core::components.alerts')

    {{-- Global KPI cards --}}
    <div class="row g-3 mb-4">

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-paper-plane text-success fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Tasa de entrega</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['delivery_rate'] ?? 0, 1) }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-envelope-open text-primary fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Open rate</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['open_rate'] ?? 0, 1) }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-mouse-pointer text-warning fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">CTR</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['ctr'] ?? 0, 1) }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-ban text-danger fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Bounce rate</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['bounce_rate'] ?? 0, 1) }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-flag text-danger fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Tasa de quejas</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['complaint_rate'] ?? 0, 2) }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-secondary-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-user-minus text-secondary fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Tasa de baja</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['unsubscribe_rate'] ?? 0, 2) }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="fas fa-euro-sign text-success fs-5"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Revenue/destinatario</p>
                            <h4 class="fw-bold mb-0">{{ number_format($stats['revenue_per_recipient'] ?? 0, 2) }} €</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Revenue chart --}}
    <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Revenue mensual atribuido</h6>
            <select id="chart-range" class="form-select form-select-sm w-auto">
                <option value="6">Últimos 6 meses</option>
                <option value="12" selected>Últimos 12 meses</option>
            </select>
        </div>
        <div class="card-body">
            <div id="revenue-chart" style="height:300px"></div>
        </div>
    </div>

    {{-- Campaigns metrics grid --}}
    <div class="card">
        <div class="card-header border-bottom">
            <h6 class="fw-bold mb-0">Métricas por campaña</h6>
        </div>
        <div class="card-body">
            <div id="campaigns-grid" style="height:400px"></div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var revenueChart = null;

    function loadRevenueChart(months) {
        $.getJSON('{{ route('remarketing.reports.chart-data') }}', { months: months }, function (res) {
            if (revenueChart) revenueChart.dispose();
            revenueChart = $('#revenue-chart').dxChart({
                dataSource: (res.labels || []).map(function (label, i) {
                    return { month: label, revenue: res.data ? res.data[i] : 0 };
                }),
                series: [{ valueField: 'revenue', argumentField: 'month', type: 'bar', color: '#b10100', name: 'Revenue' }],
                tooltip: { enabled: true },
                legend: { visible: false }
            }).dxChart('instance');
        });
    }

    loadRevenueChart(12);
    $('#chart-range').on('change', function () { loadRevenueChart($(this).val()); });

    // Campaigns DataGrid
    $('#campaigns-grid').dxDataGrid({
        dataSource: {
            store: new DevExpress.data.CustomStore({
                load: function (loadOptions) {
                    return $.getJSON('{{ route('remarketing.reports.campaigns-data') }}', loadOptions);
                }
            })
        },
        columns: [
            { dataField: 'name', caption: 'Campaña' },
            { dataField: 'sent', caption: 'Enviados', dataType: 'number', format: 'fixedPoint' },
            { dataField: 'open_rate', caption: 'Open rate', format: { type: 'fixedPoint', precision: 1 }, suffix: '%' },
            { dataField: 'ctr', caption: 'CTR', format: { type: 'fixedPoint', precision: 1 }, suffix: '%' },
            { dataField: 'bounce_rate', caption: 'Bounce rate', format: { type: 'fixedPoint', precision: 1 }, suffix: '%' },
            { dataField: 'revenue', caption: 'Revenue', dataType: 'number', format: { type: 'fixedPoint', precision: 2 }, suffix: ' €' },
            { dataField: 'completed_at', caption: 'Completado', dataType: 'date', format: 'dd/MM/yyyy' }
        ],
        paging: { pageSize: 15 },
        filterRow: { visible: true },
        sorting: { mode: 'single' }
    });
});
</script>
@endpush
