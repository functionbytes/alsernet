@extends('layouts.theme')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Métricas y gráficos</h4>
        <a href="{{ route('settings.horizon.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <!-- Key Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Trabajos procesados</p>
                    <h3 class="fw-bold mb-0">{{ number_format($metrics['total_processed'] ?? 0) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Trabajos fallidos</p>
                    <h3 class="fw-bold mb-0 text-danger">{{ number_format($metrics['total_failed'] ?? 0) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Rendimiento</p>
                    <h3 class="fw-bold mb-0">{{ number_format($metrics['throughput'] ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Tiempo de espera (s)</p>
                    <h3 class="fw-bold mb-0">{{ number_format($metrics['wait_time'] ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Throughput Chart -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="card-title fw-bold mb-3">Rendimiento últimas 24 horas</h6>
            <canvas id="throughputChart"></canvas>
        </div>
    </div>

    <!-- Performance Stats -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title fw-bold mb-3">Distribución de estado</h6>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title fw-bold mb-3">Top colas por volumen</h6>
                    <canvas id="queuesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Throughput Chart - Line Chart
    const throughputCtx = document.getElementById('throughputChart')?.getContext('2d');
    if (throughputCtx) {
        const throughputData = @json($throughput ?? []);
        const labels = Object.keys(throughputData);
        const data = Object.values(throughputData);

        new Chart(throughputCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Trabajos procesados',
                    data: data,
                    borderColor: '#90bb13',
                    backgroundColor: 'rgba(144, 187, 19, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                        }
                    }
                }
            }
        });
    }

    // Status Distribution - Pie Chart
    const statusCtx = document.getElementById('statusChart')?.getContext('2d');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Procesados', 'Fallidos', 'Pendientes'],
                datasets: [{
                    data: [
                        {{ $metrics['total_processed'] ?? 0 }},
                        {{ $metrics['total_failed'] ?? 0 }},
                        0
                    ],
                    backgroundColor: [
                        '#13C672',
                        '#FA896B',
                        '#FEC90F'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }

    // Queues by Volume - Bar Chart
    const queuesCtx = document.getElementById('queuesChart')?.getContext('2d');
    if (queuesCtx) {
        new Chart(queuesCtx, {
            type: 'bar',
            data: {
                labels: ['Default', 'Emails', 'Deliveries', 'PDF', 'Bulk', 'Notifications', 'ERP', 'High', 'AI'],
                datasets: [{
                    label: 'Trabajos en cola',
                    data: [45, 38, 22, 15, 8, 12, 18, 25, 10],
                    backgroundColor: '#90bb13',
                    borderColor: '#7a9910',
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                        }
                    },
                    y: {
                        grid: {
                            display: false,
                        }
                    }
                }
            }
        });
    }

    // Auto-refresh every 30 seconds
    setInterval(() => {
        location.reload();
    }, 30000);
</script>
@endpush
@endsection
