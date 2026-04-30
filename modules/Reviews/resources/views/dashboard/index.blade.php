@extends('layouts.theme')

@section('title', 'Dashboard de reseñas')

@section('page_header')
    @include('core::components.card', ['title' => 'Dashboard de reseñas'])
@endsection

@section('content')

    {{-- Filters bar --}}
    <div class="card card-body mb-4 border-0 shadow-sm">
        <div class="row align-items-center g-3">

            {{-- Compact sync indicator --}}
            <div class="col-md-auto">
                <div id="sync-compact-indicator"
                     class="d-flex align-items-center gap-2 px-3 py-2 rounded-1 bg-success-subtle"
                     style="cursor:pointer;min-width:120px;"
                     data-bs-toggle="collapse" data-bs-target="#sync-status-collapse"
                     title="Ver estado de sincronización">
                    <i class="fas fa-circle text-success" style="font-size:0.5rem;" id="sync-compact-dot"></i>
                    <div>
                        <div class="fw-bold small lh-1 mb-1" id="sync-compact-label">Sincronizando...</div>
                        <div class="lh-1" style="font-size:0.7rem;" id="sync-compact-time">—</div>
                    </div>
                </div>
            </div>

            {{-- Divider --}}
            <div class="col-md-auto d-none d-md-block">
                <div style="width:1px;height:36px;background:#e9ecef;"></div>
            </div>

            {{-- Date pills --}}
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

            {{-- Actions --}}
            <div class="col-md-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <a href="javascript:void(0)"
                       class="d-flex align-items-center justify-content-center rounded-circle text-muted"
                       style="width:30px;height:30px;background:#f5f6f8;"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('reviews.index') }}">Ver todas las reseñas</a></li>
                        <li><a class="dropdown-item" href="{{ route('settings.reviews.templates.index') }}">Plantillas de respuesta</a></li>
                        <li><a class="dropdown-item" href="{{ route('settings.reviews.config.index') }}">Configuración</a></li>
                        <li><a class="dropdown-item" href="{{ route('reviews.export') }}">Exportar datos</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">Imprimir</a></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    {{-- Collapsible sync status details --}}
    <div class="collapse mb-4" id="sync-status-collapse">
        @include('reviews::partials.sync-status')
    </div>

    <!-- KPI Cards — 4 per row -->
    <div class="row g-3 mb-4" id="kpi-cards">
        <div class="col-lg-6 col-md-6">
            <x-reviews::stats-widget
                title="Total reseñas"
                value="0"
                sparkId="spark-total"
                id="kpi-total"
                kpi="total_reviews"
            />
        </div>
        <div class="col-lg-6 col-md-6">
            <x-reviews::stats-widget
                title="Calificación promedio"
                value="0.0"
                sparkId="spark-rating"
                subtitle="de 5.0"
                id="kpi-avg-rating"
                kpi="avg_rating"
            />
        </div>
        <div class="col-lg-6 col-md-6">
            <x-reviews::stats-widget
                title="Sin responder"
                value="0"
                sparkId="spark-unanswered"
                id="kpi-unanswered"
                kpi="pending_replies"
            />
        </div>
        <div class="col-lg-6 col-md-6">
            <x-reviews::stats-widget
                title="Tasa de respuesta"
                value="0%"
                icon="fas fa-percentage"
                id="kpi-response-rate"
                kpi="response_rate"
            />
        </div>
    </div>

    <!-- Tendencias de calificación — full width -->
    <div class="row mb-4 g-3">
        <div class="col-12">
            <div class="card w-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Tendencias de calificación</h4>
                    <p class="card-subtitle mt-1">Últimos 12 meses</p>
                </div>
                <div class="card-body">
                    <div id="chart-rating-trends" style="height:300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reseñas por día — full width -->
    <div class="row mb-4 g-3">
        <div class="col-12">
            <div class="card w-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Reseñas por día</h4>
                    <p class="card-subtitle mt-1">Últimos 30 días</p>
                </div>
                <div class="card-body">
                    <div id="chart-reviews-by-day" style="height:250px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Distribución + Top ubicaciones — list widgets side by side -->
    <div class="row mb-4 g-3">
        <div class="col-lg-6">
            <div class="card w-100 h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Distribución</h4>
                    <p class="card-subtitle mt-1">Por estrellas</p>
                </div>
                <div class="card-body">
                    <div id="rating-distribution-list"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card w-100 h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Top ubicaciones</h4>
                    <p class="card-subtitle mt-1">Más reseñas</p>
                </div>
                <div class="card-body">
                    <div id="location-stats-list"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Análisis de sentimiento — full width -->
    <div class="row mb-4 g-3">
        <div class="col-12">
            <div class="card w-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Análisis de sentimiento</h4>
                    <p class="card-subtitle mt-1">Últimos 30 días</p>
                </div>
                <div class="card-body">
                    <div id="chart-sentiment-trend" style="height:250px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables -->
    <div class="row g-4 mb-4">
        <div class="col-12">
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
                                    <th class="text-center">Calificación</th>
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
                <div id="recent-pagination" class="card-footer d-none">
                    <nav aria-label="Paginación reseñas recientes">
                        <ul class="pagination pagination-sm mb-0 justify-content-end" id="recent-pagination-ul"></ul>
                    </nav>
                </div>
            </div>
        </div>
        <div class="col-12">
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
                                    <th class="text-center">Calificación</th>
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
                <div id="attention-pagination" class="card-footer d-none">
                    <nav aria-label="Paginación requieren atención">
                        <ul class="pagination pagination-sm mb-0 justify-content-end" id="attention-pagination-ul"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    @include('reviews::partials.velocity-widget')
    @include('reviews::partials.response-time-widget')

@endsection

@push('styles')
<style>
    .brand-box-red   { background: #fce8e8; color: #b10100; }
    .brand-box-dark  { background: #e8e8e8; color: #333333; }
    .brand-box-red2  { background: #f5d0d0; color: #7b0000; }
    .brand-box-gray  { background: #efefef; color: #555555; }

    #rangePills .nav-link.active {
        background-color: #b10100;
        color: #fff;
    }
    #rangePills .nav-link:not(.active):hover {
        color: #b10100;
    }

    .table tbody tr.cursor-pointer:hover { background-color: #f5f6f8; cursor: pointer; }
    .table-responsive { max-height: 500px; overflow-y: auto; }
    .table-responsive::-webkit-scrollbar { width: 6px; }
    .table-responsive::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .table-responsive::-webkit-scrollbar-thumb { background: #b10100; border-radius: 10px; }
    .pagination .page-item.active .page-link { background-color: #b10100; border-color: #b10100; color: #fff; }
    .pagination .page-link { color: #b10100; }
    .pagination .page-link:focus { box-shadow: 0 0 0 0.25rem rgba(177,1,0,.25); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
$(document).ready(function () {

    // ─── Helpers ──────────────────────────────────────────────────────────
    const fmt = n => new Intl.NumberFormat('es-ES').format(n);

    function escHtml(t) {
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    const emptyState = msg =>
        `<div class="text-center py-4 text-muted">
            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
            <small>${msg}</small>
        </div>`;

    // ─── Sparklines ───────────────────────────────────────────────────────
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
        if (cmpEl) cmpEl.innerHTML = cmpBadge(cmpCurrent, cmpPrev);
    }

    // ─── Chart instances ──────────────────────────────────────────────────
    let charts = { ratingTrends: null, reviewsByDay: null, sentimentTrend: null };

    // ─── Shared ApexCharts options ────────────────────────────────────────
    const axisLabelStyle = { fontSize: '11px', colors: '#adb5bd' };

    // ─── Data loading ─────────────────────────────────────────────────────
    function loadDashboardData() {
        $.ajax({
            url: '{{ route("reviews.dashboard.data") }}',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                updateKpis(response.kpis);
                renderCharts(response);
                renderRecentReviews(response.recentReviews);
                renderAttentionNeeded(response.attentionNeeded);
            },
            error: function () {
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

        // ── Sparklines ────────────────────────────────────────────────────
        if (data.reviewsByDay && data.reviewsByDay.dailyData) {
            const d = data.reviewsByDay;
            renderSpark('spark-total',      d.dailyData.map(r => r.count || 0), '#b10100', 'area', d.currTotal,      d.prevTotal);
            renderSpark('spark-unanswered', d.dailyData.map(r => r.count || 0), '#333333', 'bar',  d.currUnanswered, d.prevUnanswered);
        }
        if (data.ratingTrends && data.ratingTrends.datasets && data.ratingTrends.datasets[0]) {
            const avgRatings = data.ratingTrends.datasets[0].data;
            const half = Math.ceil(avgRatings.length / 2);
            renderSpark('spark-rating', avgRatings, '#7b0000', 'area',
                avgRatings.slice(-half).reduce((a, b) => a + b, 0) / (half || 1),
                avgRatings.slice(0, half).reduce((a, b) => a + b, 0) / (half || 1));
        }

        // ── Tendencias de calificación (ApexCharts line) ──────────────────
        const trendsEl = document.querySelector('#chart-rating-trends');
        if (!data.ratingTrends || !data.ratingTrends.labels.length) {
            trendsEl.innerHTML = emptyState('No hay datos de tendencias aún');
        } else {
            if (charts.ratingTrends) { charts.ratingTrends.destroy(); }
            trendsEl.innerHTML = '';
            const datasets = data.ratingTrends.datasets || [];
            const yaxisConfig = datasets.length > 1
                ? [
                    { min: 0, max: 5, decimalsInFloat: 1, labels: { style: axisLabelStyle, formatter: v => v.toFixed(1) } },
                    { opposite: true, labels: { style: axisLabelStyle, formatter: v => Math.round(v) } }
                  ]
                : { min: 0, max: 5, labels: { style: axisLabelStyle, formatter: v => v.toFixed(1) } };

            charts.ratingTrends = new ApexCharts(trendsEl, {
                series: datasets.map(ds => ({ name: ds.label, data: ds.data })),
                chart: { type: 'line', height: 295, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
                colors: ['#b10100', '#333333'],
                stroke: { curve: 'smooth', width: [2, 2] },
                fill: { type: 'solid', opacity: 0 },
                xaxis: { categories: data.ratingTrends.labels, labels: { style: axisLabelStyle }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: yaxisConfig,
                grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
                tooltip: { theme: 'light', shared: true, intersect: false },
                legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'inherit' },
                markers: { size: 3, strokeWidth: 0, hover: { size: 5 } },
            });
            charts.ratingTrends.render();
        }

        // ── Reseñas por día (ApexCharts area) ────────────────────────────
        const daysEl = document.querySelector('#chart-reviews-by-day');
        if (!data.reviewsByDay || !data.reviewsByDay.labels.length) {
            daysEl.innerHTML = emptyState('No hay reseñas en los últimos 30 días');
        } else {
            if (charts.reviewsByDay) { charts.reviewsByDay.destroy(); }
            daysEl.innerHTML = '';
            const dayDatasets = (data.reviewsByDay.datasets || []).map(ds => ({ name: ds.label, data: ds.data }));
            const dayColors   = ['#b10100', '#333333'];

            charts.reviewsByDay = new ApexCharts(daysEl, {
                series: dayDatasets,
                chart: { type: 'area', height: 245, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
                colors: dayColors,
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] } },
                xaxis: { categories: data.reviewsByDay.labels, labels: { style: axisLabelStyle }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { min: 0, labels: { style: axisLabelStyle, formatter: v => Math.round(v) } },
                grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
                tooltip: { theme: 'light', shared: true, intersect: false },
                legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'inherit' },
                markers: { size: 0 },
            });
            charts.reviewsByDay.render();
        }

        // ── Distribución por estrellas (list widget) ──────────────────────
        const distDataset = data.ratingDistribution && data.ratingDistribution.datasets && data.ratingDistribution.datasets[0];
        const distData    = distDataset ? distDataset.data : [];
        const distLabels  = data.ratingDistribution ? data.ratingDistribution.labels : [];
        const distTotal   = distData.reduce((a, b) => a + b, 0);

        if (!distTotal) {
            $('#rating-distribution-list').html(emptyState('No hay reseñas aún'));
        } else {
            $('#rating-distribution-list').html(distData.map((count, i) => {
                const pct    = ((count / distTotal) * 100).toFixed(1);
                const isLast = i === distData.length - 1;
                return `<div class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'}">
                    <div class="d-flex align-items-center">
                        <div class="p-2 brand-box-red rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                            <i class="fas fa-star"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">${escHtml(distLabels[i] || '')}</h6>
                            <p class="fs-3 mb-0 text-muted">${fmt(count)} reseñas</p>
                        </div>
                    </div>
                    <h6 class="mb-0 fw-semibold">${pct}%</h6>
                </div>`;
            }).join(''));
        }

        // ── Top ubicaciones (list widget) ─────────────────────────────────
        const locDataset = data.locationStats && data.locationStats.datasets && data.locationStats.datasets[0];
        const locLabels  = data.locationStats ? data.locationStats.labels : [];
        const locData    = locDataset ? locDataset.data : [];
        const locTotal   = locData.reduce((a, b) => a + b, 0);

        if (!locLabels.length) {
            $('#location-stats-list').html(emptyState('No hay ubicaciones con reseñas'));
        } else {
            $('#location-stats-list').html(locLabels.slice(0, 5).map((name, i) => {
                const count  = locData[i] || 0;
                const pct    = locTotal > 0 ? ((count / locTotal) * 100).toFixed(1) : '0.0';
                const isLast = i === Math.min(locLabels.length, 5) - 1;
                return `<div class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'}">
                    <div class="d-flex align-items-center">
                        <div class="p-2 brand-box-dark rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">${escHtml(name)}</h6>
                            <p class="fs-3 mb-0 text-muted">${fmt(count)} reseñas</p>
                        </div>
                    </div>
                    <h6 class="mb-0 fw-semibold">${pct}%</h6>
                </div>`;
            }).join(''));
        }

        // ── Análisis de sentimiento (ApexCharts area) ─────────────────────
        const sentimentEl = document.querySelector('#chart-sentiment-trend');
        if (!data.sentimentTrend || !data.sentimentTrend.labels.length) {
            sentimentEl.innerHTML = emptyState('No hay datos de sentimiento');
        } else {
            if (charts.sentimentTrend) { charts.sentimentTrend.destroy(); }
            sentimentEl.innerHTML = '';
            charts.sentimentTrend = new ApexCharts(sentimentEl, {
                series: (data.sentimentTrend.datasets || []).map(ds => ({ name: ds.label, data: ds.data })),
                chart: { type: 'area', height: 245, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
                colors: ['#b10100', '#333333', '#adb5bd'],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] } },
                xaxis: { categories: data.sentimentTrend.labels, labels: { style: axisLabelStyle }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { min: 0, labels: { style: axisLabelStyle, formatter: v => Math.round(v) } },
                grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
                tooltip: { theme: 'light', shared: true, intersect: false },
                legend: { position: 'top', fontFamily: 'inherit' },
                markers: { size: 0 },
            });
            charts.sentimentTrend.render();
        }
    }

    // ─── Paginated table helper ───────────────────────────────────────────
    function makePaginator(allRows, perPage, renderRow, tbodySelector, paginationId, ulId, colSpan) {
        let page = 0;
        const totalPages = () => Math.max(1, Math.ceil(allRows.length / perPage));

        function renderPagination() {
            const tp = totalPages();
            const ul = $(`#${ulId}`);
            ul.empty();

            // Previous
            ul.append(
                `<li class="page-item ${page === 0 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-action="prev" aria-label="Anterior">
                        <i class="fas fa-chevron-left" style="font-size:0.65rem;"></i>
                    </a>
                </li>`
            );

            // Page numbers (show max 5 around current)
            const start = Math.max(0, Math.min(page - 2, tp - 5));
            const end   = Math.min(tp, start + 5);
            for (let i = start; i < end; i++) {
                ul.append(
                    `<li class="page-item ${i === page ? 'active' : ''}">
                        <a class="page-link" href="#" data-action="goto" data-page="${i}">${i + 1}</a>
                    </li>`
                );
            }

            // Next
            ul.append(
                `<li class="page-item ${page >= tp - 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-action="next" aria-label="Siguiente">
                        <i class="fas fa-chevron-right" style="font-size:0.65rem;"></i>
                    </a>
                </li>`
            );
        }

        function draw() {
            const tbody = $(tbodySelector);
            tbody.empty();
            const slice = allRows.slice(page * perPage, (page + 1) * perPage);

            if (slice.length === 0) {
                tbody.append(`<tr><td colspan="${colSpan}" class="text-center text-muted py-4">Sin registros</td></tr>`);
            } else {
                slice.forEach(row => tbody.append(renderRow(row)));
            }

            $(`#${paginationId}`).toggleClass('d-none', allRows.length <= perPage);
            renderPagination();
        }

        $(`#${ulId}`).on('click', 'a.page-link', function (e) {
            e.preventDefault();
            const action = $(this).data('action');
            const tp = totalPages();
            if (action === 'prev' && page > 0)          { page--; draw(); }
            else if (action === 'next' && page < tp - 1) { page++; draw(); }
            else if (action === 'goto')                   { page = parseInt($(this).data('page')); draw(); }
        });

        draw();
    }

    // ─── Recent reviews table ─────────────────────────────────────────────
    function renderRecentReviews(reviews) {
        const tbody = $('#recent-reviews-table tbody');

        if (!reviews || reviews.length === 0) {
            tbody.empty().append(`<tr><td colspan="5" class="text-center text-muted py-4">
                <i class="fas fa-inbox me-2"></i> No hay reseñas recientes
            </td></tr>`);
            $('#recent-pagination').addClass('d-none');
            return;
        }

        makePaginator(reviews, 5, review => {
            const stars = generateStars(review.star_rating);
            const statusBadge = review.has_reply
                ? '<span class="badge brand-box-dark">Respondida</span>'
                : '<span class="badge brand-box-red2">Pendiente</span>';
            return `<tr>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-semibold">${escHtml(review.reviewer_name)}</span>
                        ${review.comment ? `<small class="text-muted">${escHtml(review.comment.substring(0, 50))}${review.comment.length > 50 ? '...' : ''}</small>` : ''}
                    </div>
                </td>
                <td><small>${escHtml(review.location_name)}</small></td>
                <td class="text-center">${stars}</td>
                <td class="text-center">${statusBadge}</td>
                <td><small class="text-muted">${review.review_time || '-'}</small></td>
            </tr>`;
        }, '#recent-reviews-table tbody', 'recent-pagination', 'recent-pagination-ul', 5);
    }

    // ─── Attention table ──────────────────────────────────────────────────
    function renderAttentionNeeded(reviews) {
        const tbody = $('#attention-table tbody');
        $('#attention-count').text(reviews.length);

        if (!reviews || reviews.length === 0) {
            tbody.empty().append(`<tr><td colspan="5" class="text-center text-muted py-4">
                <i class="fas fa-check-circle me-2"></i> No hay reseñas que requieran atención
            </td></tr>`);
            $('#attention-pagination').addClass('d-none');
            return;
        }

        makePaginator(reviews, 5, review => {
            const stars = generateStars(review.star_rating);
            const priorityBadge = review.priority === 'high'
                ? '<span class="badge brand-box-red">Alta</span>'
                : '<span class="badge brand-box-gray">Media</span>';
            return `<tr class="cursor-pointer" onclick="window.location.href='{{ route('reviews.index') }}?review_id=${review.id}'">
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-semibold">${escHtml(review.reviewer_name)}</span>
                        ${review.comment ? `<small class="text-muted">${escHtml(review.comment.substring(0, 80))}${review.comment.length > 80 ? '...' : ''}</small>` : ''}
                    </div>
                </td>
                <td><small>${escHtml(review.location_name)}</small></td>
                <td class="text-center">${stars}</td>
                <td class="text-center">${priorityBadge}</td>
                <td><small class="text-muted">${review.review_time || '-'}</small></td>
            </tr>`;
        }, '#attention-table tbody', 'attention-pagination', 'attention-pagination-ul', 5);
    }

    function generateStars(rating) {
        const map = { ONE: 1, TWO: 2, THREE: 3, FOUR: 4, FIVE: 5 };
        const n = (typeof rating === 'string' && map[rating]) ? map[rating] : (parseInt(rating) || 0);
        return `<span class="fw-semibold">${n}<small class="text-muted fw-normal">/5</small></span>`;
    }

    loadDashboardData();
    setInterval(loadDashboardData, 300000);
});

// ─── KPI polling ──────────────────────────────────────────────────────────
(function () {
    const kpiUrl = '{{ route("reviews.dashboard.kpis") }}';

    function refreshKpis() {
        $.get(kpiUrl, function (data) {
            $('[data-kpi]').each(function () {
                const key = $(this).data('kpi');
                if (data[key] !== undefined) {
                    const $el   = $(this);
                    const oldVal = $el.text();
                    const newVal = String(data[key]);
                    if (oldVal !== newVal && oldVal !== '—') {
                        $el.fadeOut(200, function () { $el.text(newVal).fadeIn(200); });
                    } else {
                        $el.text(newVal);
                    }
                }
            });
        }).fail(function () {
            console.warn('KPI polling failed');
        });
    }

    setInterval(refreshKpis, 60000);
})();

// ─── Compact sync indicator ───────────────────────────────────────────────
(function () {
    const syncStatusUrl = '{{ route("reviews.sync-status.index") }}';

    function updateCompactIndicator() {
        $.get(syncStatusUrl).done(function (res) {
            if (!res.success || !res.data || !res.data.length) {
                setIndicator('bg-secondary-subtle', 'Sin conexión', '—', '#adb5bd');
                return;
            }
            const hasExpired = res.data.some(c => c.status !== 'active');
            const lastSync   = res.data[0].last_sync_at;
            const timeStr    = lastSync ? formatSyncTime(lastSync) : '—';

            if (hasExpired) {
                setIndicator('bg-warning-subtle', 'Expirada', timeStr, '#FEC90F');
            } else {
                setIndicator('bg-success-subtle', 'Activo', timeStr, '#13C672');
            }
        }).fail(function () {
            setIndicator('bg-secondary-subtle', 'Sin conexión', '—', '#adb5bd');
        });
    }

    function setIndicator(bgClass, label, time, dotColor) {
        const el = document.getElementById('sync-compact-indicator');
        if (!el) return;
        el.className = `d-flex align-items-center gap-2 px-3 py-2 rounded-1 ${bgClass}`;
        document.getElementById('sync-compact-dot').style.color   = dotColor;
        document.getElementById('sync-compact-label').textContent = label;
        document.getElementById('sync-compact-time').textContent  = 'Sync: ' + time;
    }

    function formatSyncTime(isoString) {
        try {
            const d = new Date(isoString);
            return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' }) +
                   ' ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        } catch (e) { return '—'; }
    }

    updateCompactIndicator();
    setInterval(updateCompactIndicator, 60000);
})();
</script>
@endpush
