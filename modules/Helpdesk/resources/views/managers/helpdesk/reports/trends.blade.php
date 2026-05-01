@extends('layouts.full')
@section('title', 'Tendencias · Helpdesk')
@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4"><i class="fas fa-chart-line text-primary me-2"></i>Tendencias</h1>
    <p class="text-muted">Evolución de los últimos 30 días.</p>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small text-muted">Mensajes recibidos</h6>
                    <canvas id="chart-messages" height="160"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small text-muted">Conversaciones cerradas</h6>
                    <canvas id="chart-closed" height="160"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small text-muted">CSAT promedio</h6>
                    <canvas id="chart-csat" height="160"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small text-muted">Distribución por canal</h6>
                    <canvas id="chart-channels" height="160"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$.get("{{ route('manager.helpdesk.reports.trends.data') }}").done(function (data) {
    const colors = ['#b10100', '#13C672', '#FEC90F', '#FA896B', '#0084FF', '#6c757d'];
    new Chart(document.getElementById('chart-messages'), {
        type: 'line',
        data: { labels: data.labels, datasets: [{ data: data.messages, borderColor: '#b10100', tension: 0.3, fill: false }] },
        options: { plugins: { legend: { display: false } } },
    });
    new Chart(document.getElementById('chart-closed'), {
        type: 'line',
        data: { labels: data.labels, datasets: [{ data: data.closed, borderColor: '#13C672', tension: 0.3, fill: false }] },
        options: { plugins: { legend: { display: false } } },
    });
    new Chart(document.getElementById('chart-csat'), {
        type: 'line',
        data: { labels: data.labels, datasets: [{ data: data.csat, borderColor: '#FEC90F', tension: 0.3, fill: false }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 5 } } },
    });
    new Chart(document.getElementById('chart-channels'), {
        type: 'doughnut',
        data: { labels: Object.keys(data.channels || {}), datasets: [{ data: Object.values(data.channels || {}), backgroundColor: colors }] },
    });
});
</script>
@endpush
@endsection
