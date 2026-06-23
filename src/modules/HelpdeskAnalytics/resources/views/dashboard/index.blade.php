@extends('layouts.theme')
@section('title', 'Analytics · Helpdesk')
@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-chart-line text-primary me-2"></i>Analytics del Helpdesk
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        Metricas omnicanal de conversaciones: volumen, CSAT, tiempos de respuesta, rendimiento de agentes y salud de clientes.
    </p>
    <form id="filters" class="ms-auto order-2 d-flex gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1" for="f-from">Desde</label>
            <input type="date" id="f-from" name="from" class="form-control form-control-sm">
        </div>
        <div>
            <label class="form-label small mb-1" for="f-to">Hasta</label>
            <input type="date" id="f-to" name="to" class="form-control form-control-sm">
        </div>
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-filter me-1"></i>Aplicar
        </button>
    </form>
</div>

<div class="row g-3 mb-4" id="kpi-cards">
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Conversaciones</div><div class="h4 fw-bold mb-0" id="kpi-conversations">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Cerradas</div><div class="h4 fw-bold mb-0" id="kpi-closed">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Abiertas (ahora)</div><div class="h4 fw-bold mb-0" id="kpi-open">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">1ª respuesta (media)</div><div class="h4 fw-bold mb-0" id="kpi-frt">—</div>
        </div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">CSAT medio</div><div class="h4 fw-bold mb-0" id="kpi-csat">—</div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h6 class="fw-semibold mb-3">Tendencia diaria</h6>
            <canvas id="chart-trends" height="120"></canvas>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h6 class="fw-semibold mb-3">Por canal</h6>
            <canvas id="chart-channels" height="200"></canvas>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h6 class="fw-semibold mb-3">Salud de clientes</h6>
            <canvas id="chart-customers" height="200"></canvas>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body pb-0"><h6 class="fw-semibold mb-0">Rendimiento por agente</h6></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Agente</th><th>Cerradas</th><th>CSAT</th><th>1ª resp.</th><th>Mensajes</th></tr></thead>
                    <tbody id="agent-rows"><tr><td colspan="5" class="text-center text-muted py-3">Cargando...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(function () {
    const dataUrl = @json(route('helpdeskanalytics.data'));
    const charts = {};

    function destroyChart(id) {
        if (charts[id]) { charts[id].destroy(); delete charts[id]; }
    }

    function secs(s) {
        if (!s) return '—';
        if (s < 60) return s + 's';
        if (s < 3600) return Math.round(s / 60) + 'm';
        return Math.round(s / 3600) + 'h';
    }

    function renderAgents(rows) {
        if (!rows.length) {
            $('#agent-rows').html('<tr><td colspan="5" class="text-center text-muted py-3">Sin datos en el rango.</td></tr>');
            return;
        }
        $('#agent-rows').html(rows.map(function (a) {
            return '<tr>' +
                '<td>' + $('<div>').text(a.name).html() + '</td>' +
                '<td>' + a.closed_count + '</td>' +
                '<td>' + (a.csat_avg || '—') + '</td>' +
                '<td>' + secs(a.avg_response_seconds) + '</td>' +
                '<td>' + a.message_count + '</td>' +
                '</tr>';
        }).join(''));
    }

    function load() {
        $.get(dataUrl, $('#filters').serialize()).done(function (res) {
            const o = res.overview || {};
            $('#kpi-conversations').text(o.conversations ?? 0);
            $('#kpi-closed').text(o.closed ?? 0);
            $('#kpi-open').text(o.open ?? 0);
            $('#kpi-frt').text(secs(o.avg_first_response_seconds));
            $('#kpi-csat').text(o.csat_avg ?? 0);

            const trends = res.trends || [];
            destroyChart('chart-trends');
            charts['chart-trends'] = new Chart(document.getElementById('chart-trends'), {
                type: 'line',
                data: {
                    labels: trends.map(t => t.date),
                    datasets: [
                        { label: 'Creadas', data: trends.map(t => t.created), borderColor: '#90bb13', tension: 0.3 },
                        { label: 'Cerradas', data: trends.map(t => t.closed), borderColor: '#6c757d', tension: 0.3 },
                    ],
                },
                options: { responsive: true, maintainAspectRatio: false },
            });

            const channels = res.channels || [];
            destroyChart('chart-channels');
            charts['chart-channels'] = new Chart(document.getElementById('chart-channels'), {
                type: 'doughnut',
                data: { labels: channels.map(c => c.channel), datasets: [{ data: channels.map(c => c.count) }] },
                options: { responsive: true, maintainAspectRatio: false },
            });

            const cust = res.customers || {};
            destroyChart('chart-customers');
            charts['chart-customers'] = new Chart(document.getElementById('chart-customers'), {
                type: 'doughnut',
                data: {
                    labels: ['Saludables', 'Neutrales', 'En riesgo'],
                    datasets: [{ data: [cust.healthy || 0, cust.neutral || 0, cust.at_risk || 0], backgroundColor: ['#13C672', '#FEC90F', '#FA896B'] }],
                },
                options: { responsive: true, maintainAspectRatio: false },
            });

            renderAgents(res.agents || []);
        }).fail(function () {
            toastr.error('No se pudieron cargar las metricas.');
        });
    }

    $('#filters').on('submit', function (e) { e.preventDefault(); load(); });
    load();
});
</script>
@endpush
