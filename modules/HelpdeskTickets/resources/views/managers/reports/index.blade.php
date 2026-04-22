@extends('layouts.theme')

@section('title', 'Reports - Helpdesk')

@section('content')
    {{-- Page header --}}
    <div class="bg-white border-bottom sticky-top z-10">
        <div class="d-flex align-items-center px-4 py-3">
            <h4 class="mb-0 fw-bold">
                <i class="fas fa-chart-bar me-2 text-primary"></i>
                Reports
            </h4>
            <span class="ms-auto text-muted small">Ultimos 30 dias</span>
        </div>
    </div>

    <div class="p-4">
        @include('core::components.alerts')

        {{-- KPI cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1 small">SLA compliance</p>
                        @php
                            $slaRate = $data['sla_total'] > 0
                                ? round((($data['sla_total'] - $data['sla_breached']) / $data['sla_total']) * 100, 1)
                                : 100;
                        @endphp
                        <h3 class="fw-bold mb-0">{{ $slaRate }}%</h3>
                        <small class="text-muted">{{ $data['sla_breached'] }} breach / {{ $data['sla_total'] }} tickets</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1 small">Tiempo medio resolucion</p>
                        @php
                            $avgMinutes = $data['avg_resolution_minutes'] ?? 0;
                            $avgHours = round($avgMinutes / 60, 1);
                        @endphp
                        <h3 class="fw-bold mb-0">{{ $avgHours }}h</h3>
                        <small class="text-muted">{{ number_format($avgMinutes) }} minutos promedio</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1 small">Tickets creados (30d)</p>
                        <h3 class="fw-bold mb-0">{{ number_format($data['volume']->sum('created')) }}</h3>
                        <small class="text-muted">Total en el periodo</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1 small">Tickets resueltos (30d)</p>
                        <h3 class="fw-bold mb-0">{{ number_format($data['volume']->sum('resolved')) }}</h3>
                        <small class="text-muted">Total en el periodo</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Volume + Status row --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Volumen ultimos 30 dias</h6>
                        <canvas id="chartVolume" height="80"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Por estado</h6>
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Priority + Agents + CSAT row --}}
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Por prioridad</h6>
                        <canvas id="chartPriority" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Top 5 agentes</h6>
                        <canvas id="chartAgents" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">CSAT distribucion</h6>
                        <canvas id="chartCsat" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    const D = @json($data);

    const COLORS = {
        primary: '#90bb13',
        success: '#13C672',
        warning: '#FEC90F',
        danger:  '#FA896B',
        red:     '#E74C3C',
    };

    // Volume line chart
    new Chart(document.getElementById('chartVolume'), {
        type: 'line',
        data: {
            labels: D.volume.map(function (d) { return d.day.substr(5); }),
            datasets: [
                {
                    label: 'Creados',
                    data: D.volume.map(function (d) { return d.created; }),
                    borderColor: COLORS.primary,
                    backgroundColor: COLORS.primary + '22',
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: 'Resueltos',
                    data: D.volume.map(function (d) { return d.resolved; }),
                    borderColor: COLORS.success,
                    backgroundColor: COLORS.success + '22',
                    tension: 0.3,
                    fill: true,
                },
            ],
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } },
        },
    });

    // Status doughnut
    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels: D.by_status.map(function (s) { return s.label; }),
            datasets: [{
                data: D.by_status.map(function (s) { return s.value; }),
                backgroundColor: D.by_status.map(function (s) { return s.color; }),
            }],
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            cutout: '65%',
        },
    });

    // Priority bar
    new Chart(document.getElementById('chartPriority'), {
        type: 'bar',
        data: {
            labels: Object.keys(D.by_priority),
            datasets: [{
                label: 'Tickets',
                data: Object.values(D.by_priority),
                backgroundColor: [COLORS.success, COLORS.warning, COLORS.danger, COLORS.red],
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } },
        },
    });

    // Agents horizontal bar
    new Chart(document.getElementById('chartAgents'), {
        type: 'bar',
        data: {
            labels: D.top_agents.map(function (a) { return a.firstname + ' ' + (a.lastname || ''); }),
            datasets: [{
                label: 'Resueltos',
                data: D.top_agents.map(function (a) { return a.resolved_count; }),
                backgroundColor: COLORS.primary,
            }],
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } },
        },
    });

    // CSAT distribution
    new Chart(document.getElementById('chartCsat'), {
        type: 'bar',
        data: {
            labels: ['1 ★', '2 ★', '3 ★', '4 ★', '5 ★'],
            datasets: [{
                label: 'Valoraciones',
                data: [1, 2, 3, 4, 5].map(function (r) { return D.csat_distribution[r] || 0; }),
                backgroundColor: [COLORS.red, COLORS.danger, COLORS.warning, '#8BC34A', COLORS.success],
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } },
        },
    });
}());
</script>
@endpush
