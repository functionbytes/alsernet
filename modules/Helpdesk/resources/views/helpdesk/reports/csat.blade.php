@extends('layouts.theme')
@section('title', 'Reporte CSAT · Helpdesk')
@section('page_header')
    @include('core::components.card', ['title' => 'Reporte CSAT · Helpdesk'])
@endsection

@section('content')

    <div class="widget-content dashboard-page">

        {{-- Filters Bar --}}
        <div class="card card-body mb-4 border-0 shadow-sm">
            <div class="row align-items-center g-3">
                <div class="col-md-auto">
                    <div class="d-flex align-items-center gap-3 px-3 py-2 rounded-1 p-2 dashboard-health-badge">
                        <div class="position-relative d-flex align-items-center justify-content-center rounded-circle" style="width:24px;height:24px;">
                            <i class="fas fa-smile"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-baseline gap-1 lh-1 mb-1">
                                <span class="fw-bold">CSAT</span>
                                <span class="small fw-semibold">satisfacción</span>
                            </div>
                            <div class="lh-1" style="font-size:0.7rem;">Conversaciones del Helpdesk</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-auto d-none d-md-block">
                    <div style="width:1px;height:36px;background:#e9ecef;"></div>
                </div>
                <div class="col-md-auto">
                    <label class="form-label form-label-sm mb-1">Desde</label>
                    <input type="date" id="filter-from" class="form-control form-control-sm">
                </div>
                <div class="col-md-auto">
                    <label class="form-label form-label-sm mb-1">Hasta</label>
                    <input type="date" id="filter-to" class="form-control form-control-sm">
                </div>
                <div class="col-md"></div>
                <div class="col-md-auto d-flex align-items-center gap-2">
                    <button id="btn-apply" class="btn btn-sm btn-primary" title="Aplicar filtros">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a id="btn-export" href="#" class="btn btn-sm btn-light" title="Exportar CSV">
                        <i class="fas fa-file-csv"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="row mb-4 g-3">

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Promedio global</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="metric-avg"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">de 5 estrellas</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                                        <i class="fas fa-star text-warning"></i>
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
                                <h5 class="card-title fw-semibold mb-3">Total valoraciones</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="metric-total"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">en el período</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                                        <i class="fas fa-poll text-primary"></i>
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
                                <h5 class="card-title fw-semibold mb-3">Score</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="metric-nps"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">% prom. − % det.</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                                        <i class="fas fa-thumbs-up text-success"></i>
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
                                <h5 class="card-title fw-semibold mb-3">Tendencia</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="metric-trend"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">1ª vs 2ª mitad del período</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                                        <i class="fas fa-arrow-trend-up text-info"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Distribution + Trend --}}
        <div class="row mb-4 g-3">
            <div class="col-lg-5">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Distribución de ratings</h4>
                        <p class="card-subtitle mt-1">Valoraciones por número de estrellas</p>
                    </div>
                    <div class="card-body">
                        <div id="chart-distribution" style="height:260px;">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="spinner-border spinner-border-sm text-secondary"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Tendencia promedio</h4>
                        <p class="card-subtitle mt-1">Evolución diaria del CSAT en el período</p>
                    </div>
                    <div class="card-body">
                        <div id="chart-trend" style="height:260px;">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="spinner-border spinner-border-sm text-secondary"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top agents --}}
        <div class="row mb-4 g-3">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Top agentes por CSAT</h4>
                        <p class="card-subtitle mt-1">Promedio de valoración por agente en el período</p>
                    </div>
                    <div class="card-body">
                        <div id="chart-agents" style="height:280px;">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="spinner-border spinner-border-sm text-secondary"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Low ratings table --}}
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">
                            <i class="fas fa-exclamation-triangle text-danger me-1"></i>Valoraciones bajas
                        </h4>
                        <p class="card-subtitle mt-1">Últimos comentarios con 2 estrellas o menos</p>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="table-low-ratings">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Agente</th>
                                        <th scope="col">Puntuación</th>
                                        <th scope="col">Comentario</th>
                                        <th scope="col">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-low-ratings">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Cargando datos...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

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
    .skeleton-title { height: 32px; width: 80px; }

    /* Mismo estilo del "health badge" que panel/dashboard: vive en el bloque
       de estilos propio de esa vista y no se hereda solo con la clase, hay
       que traerlo tal cual para que el badge de esta pagina se vea igual. */
    .dashboard-page .dashboard-health-badge {
        background-color: rgba(144, 187, 19, 0.12);
        color: #555555;
    }
    .dashboard-page .dashboard-health-badge .fw-bold { color: #90bb13; }

    /* Misma reasignacion de paleta que el dashboard: el tema base pinta el
       estado "danger" en rosa/rojo, que no es de la paleta de la casa. Aqui
       se reasigna al verde de marca, igual que en panel/dashboard. */
    .dashboard-page .text-danger { color: #90bb13 !important; }
    .dashboard-page .bg-danger-subtle {
        background-color: rgba(144, 187, 19, 0.12) !important;
        color: #90bb13 !important;
        border-color: #90bb13 !important;
    }
    .dashboard-page .bg-danger { background-color: #90bb13 !important; }
    .dashboard-page .text-warning { color: #7d9f10 !important; }
    .dashboard-page .bg-warning-subtle {
        background-color: rgba(144, 187, 19, 0.12) !important;
        color: #7d9f10 !important;
        border-color: #b6d34a !important;
    }
    .dashboard-page .bg-success-subtle { background-color: rgba(144, 187, 19, 0.12) !important; }
    .dashboard-page .text-success { color: #90bb13 !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
(function () {
    const dataUrl = "{{ route('manager.helpdesk.reports.csat.data') }}";
    const exportUrl = "{{ route('manager.helpdesk.exports.csat') }}";

    // Escala de gris a verde de marca: de peor (1 estrella) a mejor (5 estrellas),
    // sin rojos ni colores fuera de la paleta de la casa.
    const RATING_COLORS = ['#333333', '#555555', '#4f6b0a', '#7d9f10', '#90bb13'];

    let distChart = null, trendChart = null, agentsChart = null;

    function fmt(n) { return new Intl.NumberFormat('es-ES').format(n); }
    const emptyState = msg => `<div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i><small>${msg}</small></div>`;

    function renderStars(rating) {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            html += '<i class="fas fa-star' + (i <= rating ? '' : ' text-muted opacity-25') + ' text-warning me-1"></i>';
        }
        return html;
    }

    function escHtml(t) {
        const d = document.createElement('div');
        d.textContent = t ?? '';
        return d.innerHTML;
    }

    function updateExportLink() {
        const from = $('#filter-from').val();
        const to   = $('#filter-to').val();
        const qs   = new URLSearchParams({ date_from: from, date_to: to }).toString();
        $('#btn-export').attr('href', exportUrl + '?' + qs);
    }

    function renderDistribution(distribution) {
        if (distChart) { distChart.destroy(); distChart = null; }
        $('#chart-distribution').html('');

        const series = [1, 2, 3, 4, 5].map(i => distribution[i] || 0);
        if (series.every(v => v === 0)) {
            $('#chart-distribution').html(emptyState('Sin valoraciones en el período'));
            return;
        }

        distChart = new ApexCharts(document.querySelector('#chart-distribution'), {
            series: series,
            labels: ['1 estrella', '2 estrellas', '3 estrellas', '4 estrellas', '5 estrellas'],
            chart: { type: 'donut', height: 260, fontFamily: 'inherit' },
            colors: RATING_COLORS,
            legend: { position: 'bottom', fontFamily: 'inherit' },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: v => fmt(v) } },
            plotOptions: { pie: { donut: { size: '70%' } } },
        });
        distChart.render();
    }

    function renderTrend(trend) {
        if (trendChart) { trendChart.destroy(); trendChart = null; }
        $('#chart-trend').html('');

        if (!trend.length) {
            $('#chart-trend').html(emptyState('Sin datos de tendencia'));
            return;
        }

        trendChart = new ApexCharts(document.querySelector('#chart-trend'), {
            series: [{ name: 'Promedio', data: trend.map(r => r.avg) }],
            chart: { type: 'area', height: 260, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
            colors: ['#90bb13'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] } },
            xaxis: { categories: trend.map(r => r.day), labels: { style: { fontSize: '11px', colors: '#adb5bd' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { min: 1, max: 5, tickAmount: 4, labels: { style: { fontSize: '11px', colors: '#adb5bd' } } },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            tooltip: { theme: 'light' },
            markers: { size: 0 },
        });
        trendChart.render();
    }

    function renderAgents(byAgent) {
        if (agentsChart) { agentsChart.destroy(); agentsChart = null; }
        $('#chart-agents').html('');

        const topAgents = byAgent.slice(0, 8);
        if (!topAgents.length) {
            $('#chart-agents').html(emptyState('Sin datos de agentes'));
            return;
        }

        agentsChart = new ApexCharts(document.querySelector('#chart-agents'), {
            series: [{ name: 'Promedio CSAT', data: topAgents.map(a => a.avg) }],
            chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
            colors: ['#90bb13'],
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
            xaxis: { categories: topAgents.map(a => a.agent_name), min: 0, max: 5, tickAmount: 5, labels: { style: { fontSize: '11px', colors: '#adb5bd' } } },
            yaxis: { labels: { style: { fontSize: '12px', colors: '#555555' } } },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            dataLabels: { enabled: true, style: { colors: ['#ffffff'] } },
            tooltip: { theme: 'light' },
        });
        agentsChart.render();
    }

    function load(showToast) {
        const params = {
            date_from: $('#filter-from').val() || '',
            date_to:   $('#filter-to').val() || '',
        };

        updateExportLink();

        const $icon = $('#btn-apply i');
        $icon.addClass('fa-spin');

        $.get(dataUrl, params).done(function (data) {
            const summary = data.summary;
            const distribution = data.distribution;
            const trend = data.trend;
            const byAgent = data.by_agent;
            const lowRatings = data.low_ratings;

            // KPIs
            $('#metric-avg').text(summary.avg !== null ? summary.avg + ' ★' : '—');
            $('#metric-total').text(fmt(summary.total));

            const promoters = (distribution[4] || 0) + (distribution[5] || 0);
            const detractors = (distribution[1] || 0) + (distribution[2] || 0);
            const nps = summary.total > 0
                ? Math.round(((promoters - detractors) / summary.total) * 100)
                : 0;
            $('#metric-nps').html(
                '<span class="' + (nps >= 0 ? 'text-success' : 'text-danger') + '">' +
                (nps >= 0 ? '+' : '') + nps + '%</span>'
            );

            if (trend.length >= 2) {
                const mid = Math.floor(trend.length / 2);
                const firstHalfAvg = trend.slice(0, mid).reduce((s, r) => s + r.avg, 0) / mid;
                const secondHalfAvg = trend.slice(mid).reduce((s, r) => s + r.avg, 0) / (trend.length - mid);
                const diff = +(secondHalfAvg - firstHalfAvg).toFixed(2);
                const arrow = diff >= 0
                    ? '<i class="fas fa-arrow-up text-success me-1"></i>' + diff
                    : '<i class="fas fa-arrow-down text-danger me-1"></i>' + diff;
                $('#metric-trend').html(arrow);
            } else {
                $('#metric-trend').text('—');
            }

            renderDistribution(distribution);
            renderTrend(trend);
            renderAgents(byAgent);

            // Low ratings table
            const tbody = $('#tbody-low-ratings');
            tbody.empty();
            if (lowRatings.length === 0) {
                tbody.html('<tr><td colspan="4" class="text-center text-muted py-4">Sin valoraciones bajas en el período.</td></tr>');
            } else {
                lowRatings.forEach(function (row) {
                    const date = row.answered_at ? new Date(row.answered_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
                    tbody.append(
                        '<tr>' +
                        '<td>' + escHtml(row.agent_name) + '</td>' +
                        '<td>' + renderStars(row.rating) + '</td>' +
                        '<td class="text-muted">' + escHtml(row.comment || '—') + '</td>' +
                        '<td class="text-nowrap text-muted small">' + date + '</td>' +
                        '</tr>'
                    );
                });
            }

            if (showToast) { toastr.success('Reporte CSAT actualizado'); }
        }).fail(function () {
            $('.kpi-value').text('—');
            toastr.error('Error al cargar los datos del reporte CSAT.');
        }).always(function () {
            $icon.removeClass('fa-spin');
        });
    }

    // Default date range: last 30 days
    const today = new Date();
    const prior = new Date(today);
    prior.setDate(prior.getDate() - 30);
    $('#filter-to').val(today.toISOString().slice(0, 10));
    $('#filter-from').val(prior.toISOString().slice(0, 10));

    $('#btn-apply').on('click', function () { load(true); });

    load(false);
})();
</script>
@endpush
