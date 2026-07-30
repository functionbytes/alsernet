@extends('layouts.theme')
@section('title', 'Tendencias · Helpdesk')
@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-chart-line text-primary me-2"></i>Tendencias
    </h1>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <input type="date" id="filter-from" class="form-control form-control-sm" style="width:150px">
        <input type="date" id="filter-to"   class="form-control form-control-sm" style="width:150px">
        <select id="filter-inbox" class="form-select form-select-sm" style="width:160px">
            <option value="">Todos los inboxes</option>
        </select>
        <button id="btn-apply" class="btn btn-sm btn-primary">
            <i class="fas fa-filter me-1"></i>Aplicar
        </button>
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3" id="trends-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-volumen" type="button">
            <i class="fas fa-comments me-1"></i>Volumen
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tiempos" type="button">
            <i class="fas fa-clock me-1"></i>Tiempos
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-satisfaccion" type="button">
            <i class="fas fa-star me-1"></i>Satisfacción
        </button>
    </li>
</ul>

<div class="tab-content">

    {{-- ── Volumen ── --}}
    <div class="tab-pane fade show active" id="tab-volumen">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">Mensajes recibidos</h6>
                        <canvas id="chart-messages" height="160"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">Conversaciones creadas vs resueltas</h6>
                        <canvas id="chart-created-closed" height="160"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">Volumen por canal</h6>
                        <canvas id="chart-channels" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tiempos ── --}}
    <div class="tab-pane fade" id="tab-tiempos">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">Tiempo promedio de primera respuesta (min)</h6>
                        <canvas id="chart-frt" height="160"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">Tiempo promedio de resolución (min)</h6>
                        <canvas id="chart-resolution" height="160"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Satisfacción ── --}}
    <div class="tab-pane fade" id="tab-satisfaccion">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">CSAT promedio diario</h6>
                        <canvas id="chart-csat" height="160"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const COLORS = ['#90bb13', '#13C672', '#FEC90F', '#FA896B', '#0084FF', '#6c757d', '#9c27b0', '#ff9800'];
    const dataUrl = "{{ route('manager.helpdesk.reports.trends.data') }}";

    let charts = {};

    function destroyChart(id) {
        if (charts[id]) { charts[id].destroy(); delete charts[id]; }
    }

    function makeLine(id, labels, datasets) {
        destroyChart(id);
        const ctx = document.getElementById(id);
        if (!ctx) { return; }
        charts[id] = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                plugins: { legend: { display: datasets.length > 1 } },
                scales: { y: { beginAtZero: true } },
            },
        });
    }

    function makeBar(id, labels, datasets) {
        destroyChart(id);
        const ctx = document.getElementById(id);
        if (!ctx) { return; }
        charts[id] = new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets },
            options: {
                responsive: true,
                plugins: { legend: { display: true } },
                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
            },
        });
    }

    function load() {
        const params = {
            date_from:   $('#filter-from').val() || '',
            date_to:     $('#filter-to').val() || '',
            inbox_id:    $('#filter-inbox').val() || '',
        };

        $.get(dataUrl, params).done(function (data) {
            const labels = data.labels;

            makeLine('chart-messages', labels, [
                { label: 'Mensajes recibidos', data: data.messages, borderColor: '#90bb13', backgroundColor: 'rgba(177,1,0,0.08)', tension: 0.3, fill: true },
            ]);

            makeLine('chart-created-closed', labels, [
                { label: 'Creadas',  data: data.created, borderColor: '#0084FF', tension: 0.3, fill: false },
                { label: 'Resueltas', data: data.closed,  borderColor: '#13C672', tension: 0.3, fill: false },
            ]);

            // Stacked bar for channel volume
            const channelDatasets = (data.channel_labels || []).map(function (ch, i) {
                return {
                    label: ch,
                    data: data.channel_series[ch] || [],
                    backgroundColor: COLORS[i % COLORS.length],
                };
            });
            makeBar('chart-channels', labels, channelDatasets);

            makeLine('chart-frt', labels, [
                { label: 'Primera respuesta (min)', data: data.first_response, borderColor: '#FEC90F', backgroundColor: 'rgba(254,201,15,0.08)', tension: 0.3, fill: true },
            ]);

            makeLine('chart-resolution', labels, [
                { label: 'Resolución (min)', data: data.resolution, borderColor: '#FA896B', backgroundColor: 'rgba(250,137,107,0.08)', tension: 0.3, fill: true },
            ]);

            makeLine('chart-csat', labels, [
                { label: 'CSAT promedio', data: data.csat, borderColor: '#13C672', backgroundColor: 'rgba(19,198,114,0.08)', tension: 0.3, fill: true },
            ]);
        }).fail(function () {
            toastr.error('Error al cargar los datos del reporte.');
        });
    }

    // Set default date range (last 30 days)
    const today = new Date();
    const prior = new Date(today);
    prior.setDate(prior.getDate() - 30);
    $('#filter-to').val(today.toISOString().slice(0, 10));
    $('#filter-from').val(prior.toISOString().slice(0, 10));

    $('#btn-apply').on('click', load);

    // Re-render visible charts when switching tabs (Chart.js needs visible canvas)
    document.querySelectorAll('#trends-tabs [data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () { load(); });
    });

    load();
})();
</script>
@endpush
@endsection
