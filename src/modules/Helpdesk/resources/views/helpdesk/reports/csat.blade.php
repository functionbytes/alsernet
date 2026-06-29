@extends('layouts.theme')
@section('title', 'Reporte CSAT · Helpdesk')
@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-smile text-primary me-2"></i>Reporte CSAT
    </h1>
    <div class="ms-auto d-flex gap-2 flex-wrap align-items-end">
        <div>
            <label class="form-label form-label-sm mb-1">Desde</label>
            <input type="date" id="filter-from" class="form-control form-control-sm">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Hasta</label>
            <input type="date" id="filter-to" class="form-control form-control-sm">
        </div>
        <button id="btn-apply" class="btn btn-sm btn-primary">
            <i class="fas fa-filter me-1"></i>Aplicar
        </button>
        <a id="btn-export" href="#" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-csv me-1"></i>Exportar CSV
        </a>
    </div>
</div>

{{-- Metric cards --}}
<div class="row g-3 mb-4" id="metric-cards">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-warning-subtle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px">
                    <i class="fas fa-star text-warning"></i>
                </div>
                <h3 class="fw-bold mb-0" id="metric-avg">—</h3>
                <small class="text-muted">Promedio global</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px">
                    <i class="fas fa-poll text-primary"></i>
                </div>
                <h3 class="fw-bold mb-0" id="metric-total">—</h3>
                <small class="text-muted">Total valoraciones</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-success-subtle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px">
                    <i class="fas fa-thumbs-up text-success"></i>
                </div>
                <h3 class="fw-bold mb-0" id="metric-nps">—</h3>
                <small class="text-muted">Score (% prom. - det.)</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-info-subtle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px">
                    <i class="fas fa-arrow-trend-up text-info"></i>
                </div>
                <h3 class="fw-bold mb-0" id="metric-trend">—</h3>
                <small class="text-muted">Tendencia</small>
            </div>
        </div>
    </div>
</div>

{{-- Charts row --}}
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-uppercase small text-muted mb-3">Distribución de ratings</h6>
                <canvas id="chart-distribution" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-uppercase small text-muted mb-3">Tendencia promedio en el tiempo</h6>
                <canvas id="chart-trend" height="140"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-uppercase small text-muted mb-3">Top agentes por promedio CSAT</h6>
                <canvas id="chart-agents" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Low ratings table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pb-0 pt-3">
        <h6 class="fw-semibold mb-0">
            <i class="fas fa-exclamation-triangle text-danger me-1"></i>
            Últimas valoraciones bajas (≤ 2 estrellas)
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-low-ratings">
                <thead class="table-light">
                    <tr>
                        <th>Agente</th>
                        <th>Puntuación</th>
                        <th>Comentario</th>
                        <th>Fecha</th>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const dataUrl = "{{ route('manager.helpdesk.reports.csat.data') }}";
    const exportUrl = "{{ route('manager.helpdesk.exports.csat') }}";
    const STAR_COLORS = ['#dc3545', '#FA896B', '#FEC90F', '#13C672', '#0084FF'];
    let charts = {};

    function destroyChart(id) {
        if (charts[id]) {
            charts[id].destroy();
            delete charts[id];
        }
    }

    function renderStars(rating) {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            html += '<i class="fas fa-star' + (i <= rating ? '' : ' text-muted opacity-25') + ' text-warning me-1"></i>';
        }
        return html;
    }

    function updateExportLink() {
        const from = $('#filter-from').val();
        const to   = $('#filter-to').val();
        const qs   = new URLSearchParams({ date_from: from, date_to: to }).toString();
        $('#btn-export').attr('href', exportUrl + '?' + qs);
    }

    function load() {
        const params = {
            date_from: $('#filter-from').val() || '',
            date_to:   $('#filter-to').val() || '',
        };

        updateExportLink();

        $.get(dataUrl, params).done(function (data) {
            const summary = data.summary;
            const distribution = data.distribution;
            const trend = data.trend;
            const byAgent = data.by_agent;
            const lowRatings = data.low_ratings;

            // Metrics
            $('#metric-avg').text(summary.avg !== null ? summary.avg + ' / 5' : '—');
            $('#metric-total').text(summary.total.toLocaleString());

            // NPS-like score: % promoters (4-5) minus % detractors (1-2)
            const promoters = (distribution[4] || 0) + (distribution[5] || 0);
            const detractors = (distribution[1] || 0) + (distribution[2] || 0);
            let nps = summary.total > 0
                ? Math.round(((promoters - detractors) / summary.total) * 100)
                : 0;
            $('#metric-nps').html(
                '<span class="' + (nps >= 0 ? 'text-success' : 'text-danger') + '">' +
                (nps >= 0 ? '+' : '') + nps + '%</span>'
            );

            // Trend arrow: compare first half vs second half of period
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

            // Distribution donut
            destroyChart('chart-distribution');
            const distCtx = document.getElementById('chart-distribution');
            if (distCtx) {
                charts['chart-distribution'] = new Chart(distCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['1 estrella', '2 estrellas', '3 estrellas', '4 estrellas', '5 estrellas'],
                        datasets: [{
                            data: [
                                distribution[1] || 0,
                                distribution[2] || 0,
                                distribution[3] || 0,
                                distribution[4] || 0,
                                distribution[5] || 0,
                            ],
                            backgroundColor: STAR_COLORS,
                            borderWidth: 2,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12 } },
                        },
                    },
                });
            }

            // Trend line
            destroyChart('chart-trend');
            const trendCtx = document.getElementById('chart-trend');
            if (trendCtx && trend.length > 0) {
                charts['chart-trend'] = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: trend.map(r => r.day),
                        datasets: [{
                            label: 'Promedio',
                            data: trend.map(r => r.avg),
                            borderColor: '#90bb13',
                            backgroundColor: 'rgba(177,1,0,0.08)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { min: 1, max: 5, ticks: { stepSize: 1 } },
                        },
                    },
                });
            }

            // Top agents bar (horizontal)
            destroyChart('chart-agents');
            const agentsCtx = document.getElementById('chart-agents');
            const topAgents = byAgent.slice(0, 8);
            if (agentsCtx && topAgents.length > 0) {
                charts['chart-agents'] = new Chart(agentsCtx, {
                    type: 'bar',
                    data: {
                        labels: topAgents.map(a => a.agent_name),
                        datasets: [{
                            label: 'Promedio CSAT',
                            data: topAgents.map(a => a.avg),
                            backgroundColor: '#90bb13',
                            borderRadius: 4,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { min: 0, max: 5, ticks: { stepSize: 1 } },
                        },
                    },
                });
            }

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
                        '<td>' + $('<span>').text(row.agent_name).html() + '</td>' +
                        '<td>' + renderStars(row.rating) + '</td>' +
                        '<td class="text-muted">' + ($('<span>').text(row.comment || '—').html()) + '</td>' +
                        '<td class="text-nowrap text-muted small">' + date + '</td>' +
                        '</tr>'
                    );
                });
            }
        }).fail(function () {
            toastr.error('Error al cargar los datos del reporte CSAT.');
        });
    }

    // Default date range: last 30 days
    const today = new Date();
    const prior = new Date(today);
    prior.setDate(prior.getDate() - 30);
    $('#filter-to').val(today.toISOString().slice(0, 10));
    $('#filter-from').val(prior.toISOString().slice(0, 10));

    $('#btn-apply').on('click', load);

    load();
})();
</script>
@endpush
@endsection
