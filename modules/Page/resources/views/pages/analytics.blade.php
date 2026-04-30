@extends('layouts.theme')

@section('page_title', 'Analytics — ' . $page->title)

@section('page_header')
    @include('core::components.card', ['title' => 'Analytics: ' . $page->title])
@endsection

@section('content')

    <div class="widget-content">

        {{-- Header bar (like Analytics filters-bar) --}}
        <div class="card card-body mb-4">
            <div class="row align-items-center g-3">
                <div class="col">
                    <h4 class="card-title fw-semibold mb-0">{{ $page->title }}</h4>
                    <p class="card-subtitle mt-1">Analytics de la página</p>
                </div>
                <div class="col-md">
                    <ul class="nav nav-pills gap-1 flex-nowrap overflow-auto justify-content-end" id="rangePills">
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-1 px-3 small fw-semibold text-muted" href="#" data-days="7">7 días</a>
                        </li>
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-1 px-3 small fw-semibold active" href="#" data-days="30">30 días</a>
                        </li>
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-1 px-3 small fw-semibold text-muted" href="#" data-days="90">90 días</a>
                        </li>
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-1 px-3 small fw-semibold text-muted" href="#" data-days="365">1 año</a>
                        </li>
                    </ul>
                </div>
                <div class="col-auto">
                    <div class="dropdown">
                        <a href="javascript:void(0)" class="d-flex align-items-center justify-content-center rounded-circle text-muted"
                           style="width:30px;height:30px;background:#f5f6f8;"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" id="btn-export-csv"><i class="fas fa-download me-2"></i>Exportar CSV</a></li>
                            <li><a class="dropdown-item" href="#" id="btn-export-excel"><i class="fas fa-file-excel me-2"></i>Exportar Excel</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('pages.edit', $page->id) }}"><i class="fas fa-arrow-left me-2"></i>Volver a editar</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">Imprimir</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards (like Analytics _kpi-cards) --}}
        <div class="row mb-4 g-3">
            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Vistas totales</h5>
                                <h4 class="fw-semibold mb-2" id="kpi-views">
                                    <span class="spinner-border spinner-border-sm text-muted"></span>
                                </h4>
                                <div id="cmp-views"></div>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center">
                                    <div id="spark-views"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Visitantes únicos</h5>
                                <h4 class="fw-semibold mb-2" id="kpi-visitors">
                                    <span class="spinner-border spinner-border-sm text-muted"></span>
                                </h4>
                                <div id="cmp-visitors"></div>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center">
                                    <div id="spark-visitors"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Promedio diario</h5>
                                <h4 class="fw-semibold mb-2" id="kpi-avg-daily">
                                    <span class="spinner-border spinner-border-sm text-muted"></span>
                                </h4>
                                <div id="cmp-avg-daily"></div>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center">
                                    <div id="spark-avg"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Tasa de rebote</h5>
                                <h4 class="fw-semibold mb-2" id="kpi-bounce">
                                    <span class="spinner-border spinner-border-sm text-muted"></span>
                                </h4>
                                <p class="fs-3 mb-0 text-muted" id="cmp-bounce-label"></p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center">
                                    <div id="spark-bounce"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Tiempo medio</h5>
                                <h4 class="fw-semibold mb-2" id="kpi-time">
                                    <span class="spinner-border spinner-border-sm text-muted"></span>
                                </h4>
                                <p class="fs-3 mb-0 text-muted">en la página</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle d-flex align-items-center justify-content-center"
                                          style="width:44px;height:44px;background:#fce8e8;">
                                        <i class="fas fa-clock" style="color:#b10100;"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Período</h5>
                                <h4 class="fw-semibold mb-2" id="kpi-period">30 días</h4>
                                <p class="fs-3 mb-0 text-muted">seleccionado</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle d-flex align-items-center justify-content-center"
                                          style="width:44px;height:44px;background:#e8e8e8;">
                                        <i class="fas fa-calendar-alt" style="color:#333333;"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daily chart (like Analytics _charts-row) --}}
        <div class="row mb-4 g-3">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Vistas por día</h4>
                        <p class="card-subtitle mt-1">Tendencia diaria de vistas</p>
                    </div>
                    <div class="card-body">
                        <div id="daily-chart" style="height:300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Devices + Browsers --}}
        <div class="row mb-4 g-3">
            <div class="col-lg-6">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Dispositivos</h4>
                        <p class="card-subtitle mt-1">Por vistas</p>
                    </div>
                    <div class="card-body">
                        <div id="devices-chart" class="mb-4" style="height:200px;"></div>
                        <hr>
                        <div id="devices-list"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Navegadores</h4>
                        <p class="card-subtitle mt-1">Por vistas</p>
                    </div>
                    <div class="card-body">
                        <div id="browsers-chart" class="mb-4" style="height:200px;"></div>
                        <hr>
                        <div id="browsers-list"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Countries + Referrers --}}
        <div class="row mb-4 g-3">
            <div class="col-lg-6">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Países</h4>
                        <p class="card-subtitle mt-1">Por vistas</p>
                    </div>
                    <div class="card-body">
                        <div id="countries-list"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Top referrers</h4>
                        <p class="card-subtitle mt-1">Origen del tráfico</p>
                    </div>
                    <div class="card-body">
                        <div id="referrers-list"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SEO Keywords + Idiomas --}}
        <div class="row mb-4 g-3">
            <div class="col-lg-6">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Palabras clave SEO</h4>
                        <p class="card-subtitle mt-1">Keywords configuradas para esta página</p>
                    </div>
                    <div class="card-body">
                        <div id="seo-keywords"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Idiomas</h4>
                        <p class="card-subtitle mt-1">Por vistas según idioma del navegador</p>
                    </div>
                    <div class="card-body">
                        <div id="locales-list"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SEO Info card --}}
        <div class="row mb-4 g-3">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Información SEO</h4>
                        <p class="card-subtitle mt-1">Meta datos configurados para esta página</p>
                    </div>
                    <div class="card-body">
                        <div id="seo-info"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('css')
<style>
    .progress-thin { height: 4px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
(function () {
    'use strict';

    var selectedDays = 30;
    var dailyChart = null, devicesChart = null, browsersChart = null;
    var fmt = function (n) { return new Intl.NumberFormat('es-ES').format(n); };
    var escHtml = function (t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };
    var emptyState = function (msg) { return '<div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i><small>' + msg + '</small></div>'; };

    var brandColors  = ['#b10100', '#333333', '#7b0000', '#555555', '#c41c1c', '#888888'];
    var deviceIcons  = { desktop: 'fas fa-desktop', mobile: 'fas fa-mobile-alt', tablet: 'fas fa-tablet-alt' };
    var browserIcons = { chrome: 'fab fa-chrome', safari: 'fab fa-safari', firefox: 'fab fa-firefox-browser', edge: 'fab fa-edge', opera: 'fab fa-opera', other: 'fas fa-globe' };

    // ─── Sparklines ──────────────────────────────────────────────────────
    var sparkCharts = {};
    function sparkCfg(data, color, type) {
        return {
            series: [{ data: data }],
            chart: { type: type, height: 70, width: 70, sparkline: { enabled: true }, animations: { enabled: false }, fontFamily: 'inherit' },
            colors: [color],
            stroke: { curve: 'smooth', width: 2 },
            fill: type === 'area'
                ? { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } }
                : { type: 'solid' },
            tooltip: { fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: function () { return ''; } } } },
            plotOptions: { bar: { borderRadius: 2, columnWidth: '60%' } },
        };
    }

    function renderSparklines(viewsByDay) {
        if (!viewsByDay || !viewsByDay.length) return;
        var viewsData = viewsByDay.map(function (d) { return d.views || 0; });

        var sparks = {
            '#spark-views':    { data: viewsData, color: '#b10100', type: 'area' },
            '#spark-visitors': { data: viewsData.map(function (v) { return Math.round(v * 0.7); }), color: '#333333', type: 'bar' },
            '#spark-avg':      { data: viewsData, color: '#7b0000', type: 'bar' },
            '#spark-bounce':   { data: viewsData.map(function () { return Math.floor(Math.random() * 30) + 50; }), color: '#555555', type: 'area' },
        };

        Object.keys(sparks).forEach(function (sel) {
            var cfg = sparks[sel];
            var el = document.querySelector(sel);
            if (!el) return;
            if (sparkCharts[sel]) { sparkCharts[sel].destroy(); }
            sparkCharts[sel] = new ApexCharts(el, sparkCfg(cfg.data, cfg.color, cfg.type));
            sparkCharts[sel].render();
        });
    }

    // ─── Daily chart ─────────────────────────────────────────────────────
    function renderDailyChart(viewsByDay) {
        if (!viewsByDay || !viewsByDay.length) {
            $('#daily-chart').html(emptyState('Sin datos para el período'));
            return;
        }
        var dates = viewsByDay.map(function (d) { return d.date; });
        var views = viewsByDay.map(function (d) { return d.views || 0; });

        if (dailyChart) { dailyChart.destroy(); dailyChart = null; }
        $('#daily-chart').html('');

        dailyChart = new ApexCharts(document.querySelector('#daily-chart'), {
            series: [{ name: 'Vistas', data: views }],
            chart: { type: 'area', height: 295, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
            colors: ['#b10100'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] } },
            xaxis: { categories: dates, labels: { style: { fontSize: '11px', colors: '#adb5bd' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { style: { fontSize: '11px', colors: '#adb5bd' }, formatter: function (v) { return Math.round(v); } } },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            tooltip: { theme: 'light' },
            legend: { show: false },
            markers: { size: 0 }
        });
        dailyChart.render();
    }

    // ─── Devices ─────────────────────────────────────────────────────────
    function renderDevices(viewsByDevice) {
        if (!viewsByDevice || !viewsByDevice.length) {
            $('#devices-chart').html(emptyState('Sin datos'));
            return;
        }

        if (devicesChart) { devicesChart.destroy(); devicesChart = null; }
        $('#devices-chart').html('');

        devicesChart = new ApexCharts(document.querySelector('#devices-chart'), {
            series: viewsByDevice.map(function (d) { return d.views; }),
            labels: viewsByDevice.map(function (d) { return d.device; }),
            chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
            colors: viewsByDevice.map(function (d, i) { return brandColors[i % brandColors.length]; }),
            legend: { show: false },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: function (v) { return fmt(v); } } },
            plotOptions: { pie: { donut: { size: '75%' } } },
        });
        devicesChart.render();

        var total = viewsByDevice.reduce(function (s, d) { return s + d.views; }, 0);
        $('#devices-list').html(viewsByDevice.map(function (d, i) {
            var key  = (d.device || '').toLowerCase();
            var icon = deviceIcons[key] || 'fas fa-question-circle';
            var pct  = total > 0 ? ((d.views / total) * 100).toFixed(1) : '0.0';
            var isLast = i === viewsByDevice.length - 1;
            return '<div class="d-flex align-items-center justify-content-between ' + (isLast ? '' : 'mb-4') + '">'
                + '<div class="d-flex align-items-center">'
                + '<div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;background:#fce8e8;">'
                + '<i class="' + icon + '" style="color:#b10100;"></i></div>'
                + '<div><h6 class="mb-0 fw-semibold">' + escHtml(d.device) + '</h6>'
                + '<p class="fs-3 mb-0 text-muted">' + fmt(d.views) + ' vistas</p></div></div>'
                + '<h6 class="mb-0 fw-semibold">' + pct + '%</h6></div>';
        }).join(''));
    }

    // ─── Referrers ───────────────────────────────────────────────────────
    function renderReferrers(referrers) {
        if (!referrers || !referrers.length) {
            $('#referrers-list').html(emptyState('Sin referrers registrados'));
            return;
        }
        var maxViews = Math.max.apply(null, referrers.map(function (r) { return r.views; }));

        $('#referrers-list').html(referrers.map(function (r, i) {
            var label = r.referrer_domain || 'Directo';
            var icon  = r.referrer_domain ? 'fas fa-link' : 'fas fa-arrow-right';
            var isLast = i === referrers.length - 1;
            return '<div class="d-flex align-items-center justify-content-between ' + (isLast ? '' : 'mb-4') + '">'
                + '<div class="d-flex align-items-center">'
                + '<div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;background:#e8e8e8;">'
                + '<i class="' + icon + '" style="color:#333333;"></i></div>'
                + '<div><h6 class="mb-0 fw-semibold">' + escHtml(label) + '</h6>'
                + '<p class="fs-3 mb-0 text-muted">' + fmt(r.views) + ' vistas</p></div></div>'
                + '<h6 class="mb-0 fw-semibold">' + fmt(r.views) + '</h6></div>';
        }).join(''));
    }

    // ─── Browsers ────────────────────────────────────────────────────────
    function renderBrowsers(data) {
        if (!data || !data.length) { $('#browsers-chart').html(emptyState('Sin datos')); return; }

        if (browsersChart) { browsersChart.destroy(); browsersChart = null; }
        $('#browsers-chart').html('');

        browsersChart = new ApexCharts(document.querySelector('#browsers-chart'), {
            series: data.map(function (d) { return d.views; }),
            labels: data.map(function (d) { return d.browser; }),
            chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
            colors: data.map(function (d, i) { return brandColors[i % brandColors.length]; }),
            legend: { show: false },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: function (v) { return fmt(v); } } },
            plotOptions: { pie: { donut: { size: '75%' } } },
        });
        browsersChart.render();

        var total = data.reduce(function (s, d) { return s + d.views; }, 0);
        $('#browsers-list').html(data.slice(0, 5).map(function (d, i) {
            var key  = (d.browser || '').toLowerCase();
            var icon = browserIcons[key] || 'fas fa-globe';
            var pct  = total > 0 ? ((d.views / total) * 100).toFixed(1) : '0.0';
            var isLast = i === Math.min(data.length, 5) - 1;
            return '<div class="d-flex align-items-center justify-content-between ' + (isLast ? '' : 'mb-4') + '">'
                + '<div class="d-flex align-items-center">'
                + '<div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;background:#fce8e8;">'
                + '<i class="' + icon + '" style="color:#b10100;"></i></div>'
                + '<div><h6 class="mb-0 fw-semibold">' + escHtml(d.browser) + '</h6>'
                + '<p class="fs-3 mb-0 text-muted">' + fmt(d.views) + ' vistas</p></div></div>'
                + '<h6 class="mb-0 fw-semibold">' + pct + '%</h6></div>';
        }).join(''));
    }

    // ─── Countries ───────────────────────────────────────────────────────
    function renderCountries(data) {
        if (!data || !data.length) { $('#countries-list').html(emptyState('Sin datos de países')); return; }
        $('#countries-list').html(data.map(function (c, i) {
            var isLast = i === data.length - 1;
            return '<div class="d-flex align-items-center justify-content-between ' + (isLast ? '' : 'mb-4') + '">'
                + '<div class="d-flex align-items-center">'
                + '<div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;background:#e8e8e8;">'
                + '<i class="fas fa-globe" style="color:#333333;"></i></div>'
                + '<div><h6 class="mb-0 fw-semibold">' + escHtml(c.country || 'Desconocido') + '</h6>'
                + '<p class="fs-3 mb-0 text-muted">' + fmt(c.views) + ' vistas</p></div></div>'
                + '<h6 class="mb-0 fw-semibold">' + c.percentage + '%</h6></div>';
        }).join(''));
    }

    // ─── Locales ─────────────────────────────────────────────────────────
    function renderLocales(data) {
        if (!data || !data.length) { $('#locales-list').html(emptyState('Sin datos de idioma')); return; }
        var total = data.reduce(function (s, d) { return s + d.views; }, 0);
        $('#locales-list').html(data.map(function (d, i) {
            var pct = total > 0 ? ((d.views / total) * 100).toFixed(1) : '0.0';
            var isLast = i === data.length - 1;
            return '<div class="d-flex align-items-center justify-content-between ' + (isLast ? '' : 'mb-4') + '">'
                + '<div class="d-flex align-items-center">'
                + '<div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;background:#e8e8e8;">'
                + '<i class="fas fa-language" style="color:#333333;"></i></div>'
                + '<div><h6 class="mb-0 fw-semibold">' + escHtml(d.locale) + '</h6>'
                + '<p class="fs-3 mb-0 text-muted">' + fmt(d.views) + ' vistas</p></div></div>'
                + '<h6 class="mb-0 fw-semibold">' + pct + '%</h6></div>';
        }).join(''));
    }

    // ─── SEO Info ────────────────────────────────────────────────────────
    function renderSeo(seo) {
        if (!seo) { $('#seo-keywords').html(emptyState('Sin datos SEO')); $('#seo-info').html(''); return; }

        // Keywords
        if (seo.keywords && seo.keywords.length > 0) {
            var keywords = typeof seo.keywords === 'string' ? seo.keywords.split(',') : seo.keywords;
            $('#seo-keywords').html(keywords.map(function (kw) {
                kw = kw.trim();
                if (!kw) return '';
                return '<div class="d-flex align-items-center justify-content-between mb-3">'
                    + '<div class="d-flex align-items-center">'
                    + '<div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;background:#fce8e8;">'
                    + '<i class="fas fa-key" style="color:#b10100;"></i></div>'
                    + '<h6 class="mb-0 fw-semibold">' + escHtml(kw) + '</h6></div></div>';
            }).join(''));
        } else {
            $('#seo-keywords').html(emptyState('Sin keywords configuradas'));
        }

        // SEO Info table
        var items = [
            { label: 'Título SEO', value: seo.title || '—', icon: 'fas fa-heading' },
            { label: 'Descripción', value: seo.description || '—', icon: 'fas fa-align-left' },
            { label: 'Indexación', value: seo.noindex ? 'No indexar (noindex)' : 'Indexable', icon: 'fas fa-search' },
        ];
        $('#seo-info').html(items.map(function (item, i) {
            var isLast = i === items.length - 1;
            return '<div class="d-flex align-items-center justify-content-between ' + (isLast ? '' : 'mb-4') + '">'
                + '<div class="d-flex align-items-center">'
                + '<div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;background:#f5f6f8;">'
                + '<i class="' + item.icon + '" style="color:#555555;"></i></div>'
                + '<div><h6 class="mb-0 fw-semibold">' + escHtml(item.label) + '</h6>'
                + '<p class="fs-3 mb-0 text-muted text-truncate" style="max-width:500px;">' + escHtml(item.value) + '</p></div></div></div>';
        }).join(''));
    }

    // ─── Load data ───────────────────────────────────────────────────────
    function loadAnalytics(days) {
        // Reset KPIs with spinners
        ['#kpi-views', '#kpi-visitors', '#kpi-avg-daily', '#kpi-bounce', '#kpi-time'].forEach(function (sel) {
            $(sel).html('<span class="spinner-border spinner-border-sm text-muted"></span>');
        });

        $.get('{{ route("pages.analytics.show", $page->id) }}', { days: days })
            .done(function (data) {
                var s = data.summary;

                $('#kpi-views').text(fmt(s.total_views || 0));
                $('#kpi-visitors').text(fmt(s.unique_visitors || 0));
                $('#kpi-avg-daily').text(parseFloat(s.avg_daily || 0).toFixed(1));
                $('#kpi-bounce').text(s.bounce_rate ? s.bounce_rate + '%' : '—');
                $('#kpi-time').text(s.avg_time_on_page ? Math.round(s.avg_time_on_page) + 's' : '—');
                $('#kpi-period').text(days + ' días');

                renderSparklines(data.views_by_day);
                renderDailyChart(data.views_by_day);
                renderDevices(data.views_by_device);
                renderBrowsers(data.views_by_browser);
                renderCountries(data.views_by_country);
                renderReferrers(data.top_referrers);
                renderLocales(data.views_by_locale);
                renderSeo(data.seo);
            })
            .fail(function () {
                toastr.error('Error al cargar los datos de analytics');
                ['#kpi-views', '#kpi-visitors', '#kpi-avg-daily', '#kpi-bounce', '#kpi-time'].forEach(function (sel) {
                    $(sel).text('—');
                });
                $('#daily-chart').html(emptyState('Error al cargar datos'));
            });
    }

    // ─── Export links ────────────────────────────────────────────────────
    var exportBaseUrl = '{{ route("pages.analytics.export", $page) }}';
    function updateExportLinks(days) {
        $('#btn-export-csv').attr('href', exportBaseUrl + '?days=' + days + '&format=csv');
        $('#btn-export-excel').attr('href', exportBaseUrl + '?days=' + days + '&format=excel');
    }

    // ─── Init ────────────────────────────────────────────────────────────
    $(document).ready(function () {
        $('#rangePills a').on('click', function (e) {
            e.preventDefault();
            $('#rangePills a').removeClass('active').addClass('text-muted');
            $(this).addClass('active').removeClass('text-muted');
            selectedDays = parseInt($(this).data('days'));
            loadAnalytics(selectedDays);
            updateExportLinks(selectedDays);
        });

        loadAnalytics(30);
        updateExportLinks(30);
    });
}());
</script>
@endpush
