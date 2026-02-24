@extends('layouts.theme')

@section('title', 'Dashboard de reseñas')

@section('content')

    @include('core::components.card', ['title' => 'Dashboard de reseñas'])

    <!-- KPI Cards -->
    <div class="row g-4 mb-4" id="kpi-cards">
        <div class="col-lg-3 col-md-6">
            <x-reviews::stats-widget
                title="Total reseñas"
                value="0"
                icon="fas fa-star"
                color="warning"
                id="kpi-total"
            />
        </div>
        <div class="col-lg-3 col-md-6">
            <x-reviews::stats-widget
                title="Rating promedio"
                value="0.0"
                icon="fas fa-chart-line"
                subtitle="de 5.0"
                color="success"
                id="kpi-avg-rating"
            />
        </div>
        <div class="col-lg-3 col-md-6">
            <x-reviews::stats-widget
                title="Sin responder"
                value="0"
                icon="fas fa-exclamation-circle"
                color="danger"
                id="kpi-unanswered"
            />
        </div>
        <div class="col-lg-3 col-md-6">
            <x-reviews::stats-widget
                title="Tasa de respuesta"
                value="0%"
                icon="fas fa-percentage"
                color="info"
                id="kpi-response-rate"
            />
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Tendencias de calificación</h5>
                            <p class="text-muted mb-0 fs-2">Últimos 12 meses</p>
                        </div>
                        <span class="badge bg-light-primary text-primary rounded-pill px-3 py-2">
                            <i class="fas fa-chart-line me-1"></i> Análisis
                        </span>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <canvas id="chart-rating-trends" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
                    <h5 class="mb-1 fw-bold">Distribución</h5>
                    <p class="text-muted mb-0 fs-2">Por estrellas</p>
                </div>
                <div class="card-body pt-2">
                    <canvas id="chart-rating-distribution" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Reseñas por día</h5>
                            <p class="text-muted mb-0 fs-2">Últimos 30 días</p>
                        </div>
                        <span class="badge bg-light-success text-success rounded-pill px-3 py-2">
                            <i class="fas fa-calendar-day me-1"></i> Diario
                        </span>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <canvas id="chart-reviews-by-day" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
                    <h5 class="mb-1 fw-bold">Top ubicaciones</h5>
                    <p class="text-muted mb-0 fs-2">Más reseñas</p>
                </div>
                <div class="card-body pt-2">
                    <canvas id="chart-location-stats" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="mb-1 fw-bold">Acciones rápidas</h5>
            <p class="text-muted mb-0 fs-2">Gestiona tus reseñas</p>
        </div>
        <div class="card-body pt-3">
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <a href="{{ route('reviews.index') }}" class="btn btn-primary w-100 py-3 d-flex flex-column align-items-center">
                        <i class="fas fa-list fs-5 mb-2"></i>
                        <span>Ver todas las reseñas</span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="{{ route('settings.reviews.templates.index') }}" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center">
                        <i class="fas fa-file-alt fs-5 mb-2"></i>
                        <span>Plantillas de respuesta</span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="{{ route('settings.reviews.config.index') }}" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center">
                        <i class="fas fa-cog fs-5 mb-2"></i>
                        <span>Configuración</span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="{{ route('reviews.export') }}" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center">
                        <i class="fas fa-download fs-5 mb-2"></i>
                        <span>Exportar datos</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    /* Stats Widget Animations */
    .stats-widget {
        transition: all 0.3s ease;
        border: 0;
    }
    .stats-widget:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
    }
    .stats-widget .icon-wrapper {
        transition: all 0.3s ease;
    }
    .stats-widget:hover .icon-wrapper {
        transform: scale(1.1);
    }

    /* Card Shadows */
    .shadow-sm {
        box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
    }

    /* Empty State Styling */
    .empty-chart-state {
        min-height: 250px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .empty-chart-state i {
        color: #ddd;
    }

    /* Chart Cards */
    .card-header.bg-transparent {
        background: transparent !important;
    }

    /* Button Hover Effects */
    .btn {
        transition: all 0.3s ease;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Badge Styling */
    .badge.bg-light-primary { background: rgba(144, 187, 19, 0.1) !important; color: #90bb13 !important; }
    .badge.bg-light-success { background: rgba(19, 198, 114, 0.1) !important; color: #13C672 !important; }
    .badge.bg-light-warning { background: rgba(254, 201, 15, 0.1) !important; color: #FEC90F !important; }
    .badge.bg-light-danger { background: rgba(250, 137, 107, 0.1) !important; color: #FA896B !important; }
    .badge.bg-light-info { background: rgba(99, 102, 241, 0.1) !important; color: #6366f1 !important; }

    /* Typography */
    .fs-2 { font-size: 0.875rem !important; }
    .fs-3 { font-size: 0.9375rem !important; }

    /* Card Header Typography */
    .card-header h5 {
        font-size: 1.125rem;
        color: #2A3547;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    let charts = {
        ratingTrends: null,
        ratingDistribution: null,
        reviewsByDay: null,
        locationStats: null
    };

    function loadDashboardData() {
        $.ajax({
            url: '{{ route("reviews.dashboard.data") }}',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                updateKpis(response.kpis);
                renderCharts(response);
            },
            error: function() {
                toastr.error('Error al cargar datos del dashboard');
            }
        });
    }

    function updateKpis(kpis) {
        $('#kpi-total h4').text(kpis.total.toLocaleString());
        $('#kpi-avg-rating h4').text(kpis.avgRating.toFixed(1));
        $('#kpi-unanswered h4').text(kpis.unanswered.toLocaleString());
        $('#kpi-response-rate h4').text(kpis.responseRate.toFixed(1) + '%');
    }

    function renderCharts(data) {
        // Rating Trends
        if (charts.ratingTrends) {
            charts.ratingTrends.destroy();
        }
        const ctxTrends = document.getElementById('chart-rating-trends');
        if (data.ratingTrends.labels.length === 0) {
            showEmptyState(ctxTrends, 'No hay datos de tendencias aún');
        } else {
            charts.ratingTrends = new Chart(ctxTrends.getContext('2d'), {
                type: 'line',
                data: data.ratingTrends,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        'y-rating': {
                            type: 'linear',
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Calificación promedio'
                            },
                            min: 0,
                            max: 5
                        },
                        'y-count': {
                            type: 'linear',
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Cantidad de reseñas'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }

        // Rating Distribution
        if (charts.ratingDistribution) {
            charts.ratingDistribution.destroy();
        }
        const ctxDist = document.getElementById('chart-rating-distribution');
        const hasDistributionData = data.ratingDistribution.datasets[0].data.some(val => val > 0);
        if (!hasDistributionData) {
            showEmptyState(ctxDist, 'No hay reseñas aún');
        } else {
            charts.ratingDistribution = new Chart(ctxDist.getContext('2d'), {
                type: 'doughnut',
                data: data.ratingDistribution,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Reviews by Day
        if (charts.reviewsByDay) {
            charts.reviewsByDay.destroy();
        }
        const ctxDays = document.getElementById('chart-reviews-by-day');
        if (data.reviewsByDay.labels.length === 0) {
            showEmptyState(ctxDays, 'No hay reseñas en los últimos 30 días');
        } else {
            charts.reviewsByDay = new Chart(ctxDays.getContext('2d'), {
                type: 'line',
                data: data.reviewsByDay,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Location Stats
        if (charts.locationStats) {
            charts.locationStats.destroy();
        }
        const ctxLoc = document.getElementById('chart-location-stats');
        if (data.locationStats.labels.length === 0) {
            showEmptyState(ctxLoc, 'No hay ubicaciones con reseñas');
        } else {
            charts.locationStats = new Chart(ctxLoc.getContext('2d'), {
                type: 'bar',
                data: data.locationStats,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    function showEmptyState(canvasElement, message) {
        const parent = canvasElement.parentElement;
        canvasElement.style.display = 'none';

        let emptyDiv = parent.querySelector('.empty-chart-state');
        if (!emptyDiv) {
            emptyDiv = document.createElement('div');
            emptyDiv.className = 'empty-chart-state text-center text-muted py-5';
            parent.appendChild(emptyDiv);
        }

        emptyDiv.innerHTML = `
            <i class="fas fa-chart-bar fa-3x mb-3 opacity-50"></i>
            <p class="mb-0">${message}</p>
        `;
        emptyDiv.style.display = 'block';
    }

    // Load data on page load
    loadDashboardData();

    // Auto refresh every 5 minutes
    setInterval(loadDashboardData, 300000);
});
</script>
@endpush
