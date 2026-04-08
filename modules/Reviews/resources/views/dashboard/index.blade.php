@extends('layouts.theme')

@section('title', 'Dashboard de reseñas')

@section('content')

    @include('core::components.card', ['title' => 'Dashboard de reseñas'])

    <!-- Sync Status Widget -->
    @include('reviews::partials.sync-status')

    {{-- Filters bar --}}
    <div class="card card-body mb-4 border-0 shadow-sm">
        <div class="row align-items-center g-3">
            <div class="col-md">
                <ul class="nav nav-pills gap-1 flex-nowrap overflow-auto" id="rangePills">
                    @foreach ([
                        'last_7_days'  => '7 días',
                        'last_30_days' => '30 días',
                        'this_month'   => 'Este mes',
                        'last_month'   => 'Mes anterior',
                        'this_year'    => 'Este año',
                    ] as $key => $label)
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-1 px-3 small fw-semibold {{ ($range ?? 'last_30_days') === $key ? 'active' : 'text-muted' }}"
                               href="{{ route('reviews.dashboard', ['range' => $key]) }}">
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-md-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <a href="javascript:void(0)"
                       class="d-flex align-items-center justify-content-center rounded-circle text-muted"
                       style="width:30px;height:30px;background:#f5f6f8;"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('reviews.index') }}">Ver todas las reseñas</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('settings.reviews.templates.index') }}">Plantillas de respuesta</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('settings.reviews.config.index') }}">Configuración</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('reviews.export') }}">Exportar datos</a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">Imprimir</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4" id="kpi-cards">
        <div class="col-md-6">
            <x-reviews::stats-widget
                title="Total reseñas"
                value="0"
                sparkId="spark-total"
                id="kpi-total"
                kpi="total_reviews"
            />
        </div>
        <div class="col-md-6">
            <x-reviews::stats-widget
                title="Rating promedio"
                value="0.0"
                sparkId="spark-rating"
                subtitle="de 5.0"
                id="kpi-avg-rating"
                kpi="avg_rating"
            />
        </div>
        <div class="col-md-6">
            <x-reviews::stats-widget
                title="Sin responder"
                value="0"
                sparkId="spark-unanswered"
                id="kpi-unanswered"
                kpi="pending_replies"
            />
        </div>
        <div class="col-md-6">
            <x-reviews::stats-widget
                title="Tasa de respuesta"
                value="0%"
                icon="fas fa-percentage"
                id="kpi-response-rate"
                kpi="response_rate"
            />
        </div>
    </div>

    <!-- KPI last updated indicator -->
    <div class="text-end mb-2">
        <small class="text-muted">
            <i class="fas fa-sync-alt me-1"></i>
            Actualizado: <span data-kpi="last_updated">—</span>
        </small>
    </div>

    <!-- Charts Row 1 -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Tendencias de calificación</h4>
                    <p class="card-subtitle mt-1">Últimos 12 meses</p>
                </div>
                <div class="card-body">
                    <canvas id="chart-rating-trends" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Distribución</h4>
                    <p class="card-subtitle mt-1">Por estrellas</p>
                </div>
                <div class="card-body">
                    <canvas id="chart-rating-distribution" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Reseñas por día</h4>
                    <p class="card-subtitle mt-1">Últimos 30 días</p>
                </div>
                <div class="card-body">
                    <canvas id="chart-reviews-by-day" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Top ubicaciones</h4>
                    <p class="card-subtitle mt-1">Más reseñas</p>
                </div>
                <div class="card-body">
                    <canvas id="chart-location-stats" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Sentiment Analysis -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Análisis de sentimiento</h4>
                    <p class="card-subtitle mt-1">Últimos 30 días</p>
                </div>
                <div class="card-body">
                    <canvas id="chart-sentiment-trend" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Reseñas recientes</h4>
                    <p class="card-subtitle mt-1">Últimas 10 reseñas recibidas</p>
                </div>
                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="recent-reviews-table">
                            <thead>
                                <tr>
                                    <th>Revisor</th>
                                    <th>Ubicación</th>
                                    <th class="text-center">Rating</th>
                                    <th class="text-center">Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i> Cargando...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title fw-semibold mb-0">Requieren atención</h4>
                        <p class="card-subtitle mt-1">Sin responder y baja calificación</p>
                    </div>
                    <span class="badge bg-danger rounded-pill" id="attention-count">0</span>
                </div>
                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="attention-table">
                            <thead>
                                <tr>
                                    <th>Revisor</th>
                                    <th>Ubicación</th>
                                    <th class="text-center">Rating</th>
                                    <th class="text-center">Prioridad</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i> Cargando...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Velocity Widget -->
    @include('reviews::partials.velocity-widget')

    <!-- Response Time Analytics Widget -->
    @include('reviews::partials.response-time-widget')


@endsection

@push('styles')
<style>
    .empty-chart-state {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .empty-chart-state i { color: #ddd; }

    .table tbody tr.cursor-pointer:hover {
        background-color: #f5f6f8;
        cursor: pointer;
    }
    .table-responsive {
        max-height: 500px;
        overflow-y: auto;
    }
    .table-responsive::-webkit-scrollbar { width: 6px; }
    .table-responsive::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .table-responsive::-webkit-scrollbar-thumb { background: #90bb13; border-radius: 10px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {

    // ─── Sparkline helpers ───────────────────────────────────────────────
    const fmt = n => new Intl.NumberFormat('es-ES').format(n);

    const sparkCfg = (data, color, type) => ({
        series: [{ data }],
        chart: { type, height: 70, width: 70, sparkline: { enabled: true }, animations: { enabled: false }, fontFamily: 'inherit' },
        colors: [color],
        stroke: { curve: 'smooth', width: 2 },
        fill: type === 'area'
            ? { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } }
            : { type: 'solid' },
        tooltip: { fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: () => '' } } },
        plotOptions: { bar: { borderRadius: 2, columnWidth: '60%' } },
    });

    function cmpBadge(current, previous) {
        if (!previous || previous === 0) return '';
        const pct  = ((current - previous) / previous * 100).toFixed(1);
        const up   = parseFloat(pct) > 0;
        const sign = up ? '+' : '';
        return `<div class="d-flex align-items-center">
            <span class="me-1 rounded-circle ${up ? 'bg-success-subtle' : 'bg-danger-subtle'} d-flex align-items-center justify-content-center" style="width:20px;height:20px;">
                <i class="fas ${up ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger'}" style="font-size:0.6rem;"></i>
            </span>
            <p class="text-dark me-1 fs-3 mb-0">${sign}${Math.abs(pct)}%</p>
            <p class="fs-3 mb-0 text-muted">vs anterior</p>
        </div>`;
    }

    function renderSpark(id, data, color, type, cmpCurrent, cmpPrev) {
        const el = document.getElementById(id);
        if (!el) return;
        new ApexCharts(el, sparkCfg(data, color, type)).render();
        const cmpEl = document.getElementById(id + '-cmp');
        if (cmpEl) { cmpEl.innerHTML = cmpBadge(cmpCurrent, cmpPrev); }
    }


    let charts = {
        ratingTrends: null,
        ratingDistribution: null,
        reviewsByDay: null,
        locationStats: null,
        sentimentTrend: null
    };

    function loadDashboardData() {
        $.ajax({
            url: '{{ route("reviews.dashboard.data") }}',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                updateKpis(response.kpis);
                renderCharts(response);
                renderRecentReviews(response.recentReviews);
                renderAttentionNeeded(response.attentionNeeded);
            },
            error: function() {
                toastr.error('Error al cargar datos del dashboard');
            }
        });
    }

    function updateKpis(kpis) {
        $('[data-kpi="total_reviews"]').text(kpis.total.toLocaleString());
        $('[data-kpi="avg_rating"]').text(kpis.avgRating.toFixed(1));
        $('[data-kpi="pending_replies"]').text(kpis.unanswered.toLocaleString());
        $('[data-kpi="response_rate"]').text(kpis.responseRate.toFixed(1) + '%');
    }

    function renderCharts(data) {
        // ── Sparklines (ApexCharts) ──────────────────────────────────────
        if (data.reviewsByDay && data.reviewsByDay.dailyData) {
            const d = data.reviewsByDay;
            renderSpark('spark-total',     d.dailyData.map(r => r.count || 0), '#b10100', 'area', d.currTotal,      d.prevTotal);
            renderSpark('spark-unanswered',d.dailyData.map(r => r.count || 0), '#333333', 'bar',  d.currUnanswered, d.prevUnanswered);
        }
        if (data.ratingTrends && data.ratingTrends.datasets[0]) {
            const avgRatings = data.ratingTrends.datasets[0].data;
            const half       = Math.ceil(avgRatings.length / 2);
            renderSpark('spark-rating', avgRatings, '#7b0000', 'area',
                avgRatings.slice(-half).reduce((a, b) => a + b, 0) / (half || 1),
                avgRatings.slice(0, half).reduce((a, b) => a + b, 0) / (half || 1));
        }

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

        // Sentiment Trend
        if (charts.sentimentTrend) {
            charts.sentimentTrend.destroy();
        }
        const ctxSentiment = document.getElementById('chart-sentiment-trend');
        if (data.sentimentTrend.labels.length === 0) {
            showEmptyState(ctxSentiment, 'No hay datos de sentimiento');
        } else {
            charts.sentimentTrend = new Chart(ctxSentiment.getContext('2d'), {
                type: 'line',
                data: data.sentimentTrend,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            stacked: false,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    function renderRecentReviews(reviews) {
        const tbody = $('#recent-reviews-table tbody');
        tbody.empty();

        if (!reviews || reviews.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-inbox me-2"></i> No hay reseñas recientes
                    </td>
                </tr>
            `);
            return;
        }

        reviews.forEach(review => {
            const stars = generateStars(review.star_rating);
            const statusBadge = review.has_reply
                ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Respondida</span>'
                : '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pendiente</span>';

            tbody.append(`
                <tr>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-semibold">${escapeHtml(review.reviewer_name)}</span>
                            ${review.comment ? `<small class="text-muted">${escapeHtml(review.comment.substring(0, 50))}${review.comment.length > 50 ? '...' : ''}</small>` : ''}
                        </div>
                    </td>
                    <td><small>${escapeHtml(review.location_name)}</small></td>
                    <td class="text-center">${stars}</td>
                    <td class="text-center">${statusBadge}</td>
                    <td><small class="text-muted">${review.review_time || '-'}</small></td>
                </tr>
            `);
        });
    }

    function renderAttentionNeeded(reviews) {
        const tbody = $('#attention-table tbody');
        tbody.empty();

        $('#attention-count').text(reviews.length);

        if (!reviews || reviews.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-check-circle me-2"></i> No hay reseñas que requieran atención
                    </td>
                </tr>
            `);
            return;
        }

        reviews.forEach(review => {
            const stars = generateStars(review.star_rating);
            const priorityBadge = review.priority === 'high'
                ? '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Alta</span>'
                : '<span class="badge bg-warning"><i class="fas fa-exclamation-circle me-1"></i>Media</span>';

            tbody.append(`
                <tr class="cursor-pointer" onclick="window.location.href='{{ route('reviews.index') }}?review_id=${review.id}'">
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-semibold">${escapeHtml(review.reviewer_name)}</span>
                            ${review.comment ? `<small class="text-muted">${escapeHtml(review.comment.substring(0, 80))}${review.comment.length > 80 ? '...' : ''}</small>` : ''}
                        </div>
                    </td>
                    <td><small>${escapeHtml(review.location_name)}</small></td>
                    <td class="text-center">${stars}</td>
                    <td class="text-center">${priorityBadge}</td>
                    <td><small class="text-muted">${review.review_time || '-'}</small></td>
                </tr>
            `);
        });
    }

    function generateStars(rating) {
        const numRating = parseInt(rating) || 0;
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += i <= numRating
                ? '<i class="fas fa-star text-warning"></i>'
                : '<i class="far fa-star text-muted"></i>';
        }
        return stars;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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

// KPI polling - refresh every 60 seconds
(function() {
    const kpiUrl = '{{ route("reviews.dashboard.kpis") }}';

    function refreshKpis() {
        $.get(kpiUrl, function(data) {
            $('[data-kpi]').each(function() {
                const key = $(this).data('kpi');
                if (data[key] !== undefined) {
                    const $el = $(this);
                    const oldVal = $el.text();
                    const newVal = String(data[key]);
                    if (oldVal !== newVal && oldVal !== '—') {
                        $el.fadeOut(200, function() {
                            $el.text(newVal).fadeIn(200);
                        });
                    } else {
                        $el.text(newVal);
                    }
                }
            });
        }).fail(function() {
            console.warn('KPI polling failed');
        });
    }

    setInterval(refreshKpis, 60000);
})();
</script>
@endpush
