@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Analytics Dashboard'])

    <div class="widget-content">

        @include('analytics::dashboard.partials._filters-bar')

        @include('analytics::dashboard.partials._kpi-cards')

        @include('analytics::dashboard.partials._charts-row')

        @include('analytics::dashboard.partials._tables-row')

    </div>

    {{-- Export Modal --}}
    @can('analytics.dashboard.export')
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-semibold mb-1">Exportar datos</h5>
                        <p class="text-muted small mb-0">
                            Período: <strong id="export-period-label" class="text-dark">seleccionado</strong>
                        </p>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    @php
                        $exports = [
                            ['key' => 'overview',  'icon' => 'fa-chart-line',   'label' => 'Resumen general',      'desc' => 'Sesiones, usuarios y vistas por día'],
                            ['key' => 'pages',     'icon' => 'fa-file-alt',     'label' => 'Páginas más visitadas','desc' => 'URL, título y número de vistas'],
                            ['key' => 'countries', 'icon' => 'fa-globe',        'label' => 'Visitas por país',     'desc' => 'Sesiones y porcentaje por país'],
                            ['key' => 'channels',  'icon' => 'fa-layer-group',  'label' => 'Canales de tráfico',   'desc' => 'Origen del tráfico por canal'],
                        ];
                    @endphp
                    <div class="d-flex flex-column gap-2">
                        @foreach($exports as $e)
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 border">
                            <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                                 style="width:40px;height:40px;background:#fce8e8;">
                                <i class="fas {{ $e['icon'] }}" style="color:#b10100;"></i>
                            </div>
                            <div style="min-width:0;flex:1;">
                                <div class="fw-semibold small">{{ $e['label'] }}</div>
                                <div class="text-muted" style="font-size:0.72rem;">{{ $e['desc'] }}</div>
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <button class="btn btn-sm btn-outline-secondary" style="font-size:0.72rem;padding:3px 8px;"
                                        onclick="exportData('{{ $e['key'] }}','json')">JSON</button>
                                <button class="btn btn-sm" style="font-size:0.72rem;padding:3px 8px;background:#b10100;color:#fff;border:none;"
                                        onclick="exportData('{{ $e['key'] }}','csv')">CSV</button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endcan

@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    #visits-map { border-radius: 0 0 0 8px; }
    .country-item { padding: 10px 16px; border-bottom: 1px solid #f5f6f8; display: flex; align-items: center; cursor: pointer; transition: background 0.15s; }
    .country-item:last-child { border-bottom: none; }
    .country-item:hover { background: #f5f6f8; }
    .country-item.country-active { background: #fce8e8; }
    .map-reset-btn { position: absolute; top: 10px; right: 10px; z-index: 999; }
    .progress-thin { height: 4px; }
    .cmp-up   { color: #2e7d32; }
    .cmp-down { color: #b10100; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    /* Brand icon boxes */
    .brand-box-red   { background: #fce8e8; color: #b10100; }
    .brand-box-dark  { background: #e8e8e8; color: #333333; }
    .brand-box-red2  { background: #f5d0d0; color: #7b0000; }
    .brand-box-gray  { background: #efefef; color: #555555; }
    .brand-box-mid   { background: #f0e0e0; color: #c41c1c; }
    .brand-box-light { background: #f5f6f8; color: #888888; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function () {
    'use strict';

    let selectedRange = '{{ $range }}';
    let countriesGeoData = null, leafletMap = null, geoLayer = null, countrySessions = {};
    let dailyChart = null, browserChart = null, devicesChart = null, channelChart = null, heatmapChart = null;

    // Cache de datos exportables
    const exportCache = {};

    const routes = {
        overview:        '{{ route("api.analytics.overview") }}',
        topPages:        '{{ route("api.analytics.top-pages") }}',
        topBrowsers:     '{{ route("api.analytics.top-browsers") }}',
        topReferrers:    '{{ route("api.analytics.top-referrers") }}',
        topCountries:    '{{ route("api.analytics.top-countries") }}',
        deviceCategories:'{{ route("api.analytics.device-categories") }}',
        operatingSystems:'{{ route("api.analytics.operating-systems") }}',
        trafficSources:  '{{ route("api.analytics.traffic-sources") }}',
        sessionMetrics:  '{{ route("api.analytics.session-metrics") }}',
        realtime:        '{{ route("api.analytics.realtime") }}',
        landingPages:    '{{ route("api.analytics.landing-pages") }}',
        exitPages:       '{{ route("api.analytics.exit-pages") }}',
        channelTrend:    '{{ route("api.analytics.channel-trend") }}',
        hourlyHeatmap:   '{{ route("api.analytics.hourly-heatmap") }}',
        comparison:      '{{ route("api.analytics.comparison") }}',
        searchTerms:     '{{ route("api.analytics.search-terms") }}',
        userFlow:        '{{ route("api.analytics.user-flow") }}',
    };

    // ─── Helpers ─────────────────────────────────────────────────────────
    const fmt = n => new Intl.NumberFormat('es-ES').format(n);

    function fmtSeconds(s) {
        s = Math.round(s);
        const m = Math.floor(s / 60), sec = s % 60;
        return m + 'm ' + String(sec).padStart(2, '0') + 's';
    }

    function escHtml(t) {
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    const spinner   = () => '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';
    const emptyState = msg => `<div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i><small>${msg}</small></div>`;

    function cmpBadge(current, previous, invert = false) {
        if (!previous || previous === 0) return '';
        const pct  = ((current - previous) / previous * 100).toFixed(1);
        const up   = invert ? parseFloat(pct) < 0 : parseFloat(pct) > 0;
        const icon = up ? 'fa-arrow-up' : 'fa-arrow-down';
        const bg   = up ? 'bg-success-subtle' : 'bg-danger-subtle';
        const txt  = up ? 'text-success'      : 'text-danger';
        const sign = parseFloat(pct) > 0 ? '+' : '';
        return `<div class="d-flex align-items-center">
            <span class="me-1 rounded-circle ${bg} d-flex align-items-center justify-content-center" style="width:20px;height:20px;">
                <i class="fas ${icon} ${txt}" style="font-size:0.6rem;"></i>
            </span>
            <p class="text-dark me-1 fs-3 mb-0">${sign}${Math.abs(pct)}%</p>
            <p class="fs-3 mb-0 text-muted">vs anterior</p>
        </div>`;
    }

    // ─── Realtime ────────────────────────────────────────────────────────
    function loadRealtime() {
        $.get(routes.realtime)
            .done(function (res) {
                if (!res.success) return;
                $('#realtime-count').text(fmt(res.data.active_users ?? 0));
                const now = new Date();
                $('#realtime-updated').text('Actualizado ' + now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
            })
            .fail(function () { $('#realtime-count').text('—'); });
    }

    // ─── KPIs + Comparison ───────────────────────────────────────────────
    let currentTotals = null;

    function loadOverview() {
        $.get(routes.overview, { range: selectedRange })
            .done(function (res) {
                if (!res.success) return;
                const t = res.data.totals;
                currentTotals = t;
                exportCache['overview'] = res.data;

                $('#kpi-sessions').text(fmt(t.sessions ?? 0));
                $('#kpi-users').text(fmt(t.totalUsers ?? 0));
                $('#kpi-pageviews').text(fmt(t.screenPageViews ?? 0));
                const br = t.bounceRate ?? 0;
                $('#kpi-bounce').text((br * 100).toFixed(1) + '%');

                renderDailyChart(res.data.chart_data);
                renderSparklines(res.data.chart_data);
                loadComparison();
            })
            .fail(function () {
                $('#kpi-sessions, #kpi-users, #kpi-pageviews, #kpi-bounce').text('—');
            });
    }

    function loadComparison() {
        if (!currentTotals) return;
        $.get(routes.comparison, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !currentTotals) return;
                const prev = res.data;
                $('#cmp-sessions').html(cmpBadge(currentTotals.sessions, prev.sessions));
                $('#cmp-users').html(cmpBadge(currentTotals.totalUsers, prev.totalUsers));
                $('#cmp-pageviews').html(cmpBadge(currentTotals.screenPageViews, prev.screenPageViews));
                // Para bounce rate, bajar es bueno (invert=true)
                $('#cmp-bounce').html(cmpBadge(currentTotals.bounceRate, prev.bounceRate, true));
            });
    }

    function loadSessionMetrics() {
        $.get(routes.sessionMetrics, { range: selectedRange })
            .done(function (res) {
                if (!res.success) return;
                $('#kpi-new-users').text(fmt(res.data.new_users ?? 0));
                $('#kpi-duration').text(fmtSeconds(res.data.avg_session_duration ?? 0));
            })
            .fail(function () { $('#kpi-new-users, #kpi-duration').text('—'); });
    }

    // ─── Daily chart ─────────────────────────────────────────────────────
    function renderDailyChart(chartData) {
        if (!chartData || chartData.length === 0) {
            $('#daily-chart').html(emptyState('Sin datos para el período'));
            return;
        }
        const months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        const fmtDate = d => {
            if (!d || d.length !== 8) return d;
            return months[parseInt(d.slice(4, 6)) - 1] + ' ' + parseInt(d.slice(6, 8));
        };
        const dates    = chartData.map(r => fmtDate(r['date'] ?? ''));
        const sessions = chartData.map(r => parseInt(r['sessions'] ?? 0));
        const views    = chartData.map(r => parseInt(r['screenPageViews'] ?? 0));

        if (dailyChart) { dailyChart.destroy(); dailyChart = null; }
        $('#daily-chart').html('');

        dailyChart = new ApexCharts(document.querySelector('#daily-chart'), {
            series: [
                { name: 'Sesiones', data: sessions },
                { name: 'Vistas de página', data: views }
            ],
            chart: { type: 'area', height: 295, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
            colors: ['#b10100', '#333333'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] } },
            xaxis: { categories: dates, labels: { style: { fontSize: '11px', colors: '#adb5bd' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { style: { fontSize: '11px', colors: '#adb5bd' }, formatter: v => Math.round(v) } },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            tooltip: { theme: 'light', shared: true, intersect: false },
            legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'inherit' },
            markers: { size: 0 }
        });
        dailyChart.render();
    }

    // ─── Sparklines ──────────────────────────────────────────────────────
    let sparkCharts = {};

    function renderSparklines(chartData) {
        if (!chartData || !chartData.length) return;

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

        const sparks = {
            '#spark-sessions':  { data: chartData.map(r => parseInt(r.sessions || 0)),                              color: '#b10100', type: 'area' },
            '#spark-users':     { data: chartData.map(r => parseInt(r.totalUsers || 0)),                            color: '#333333', type: 'bar'  },
            '#spark-pageviews': { data: chartData.map(r => parseInt(r.screenPageViews || 0)),                       color: '#7b0000', type: 'bar'  },
            '#spark-bounce':    { data: chartData.map(r => parseFloat((r.bounceRate || 0) * 100).toFixed(1)),       color: '#555555', type: 'area' },
        };

        Object.entries(sparks).forEach(([sel, cfg]) => {
            const el = document.querySelector(sel);
            if (!el) return;
            if (sparkCharts[sel]) { sparkCharts[sel].destroy(); }
            sparkCharts[sel] = new ApexCharts(el, sparkCfg(cfg.data, cfg.color, cfg.type));
            sparkCharts[sel].render();
        });
    }

    // ─── Channel trend (stacked area) ────────────────────────────────────
    const channelColors = {
        organic: '#b10100', cpc: '#333333', social: '#7b0000',
        referral: '#555555', email: '#c41c1c', direct: '#888888',
        none: '#888888', '(none)': '#888888'
    };

    function loadChannelTrend() {
        $.get(routes.channelTrend, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.dates || res.data.dates.length === 0) {
                    $('#channel-trend-chart').html(emptyState('Sin datos de canales'));
                    return;
                }
                const d = res.data;
                if (channelChart) { channelChart.destroy(); channelChart = null; }
                $('#channel-trend-chart').html('');

                channelChart = new ApexCharts(document.querySelector('#channel-trend-chart'), {
                    series: d.series.map(s => ({
                        name: s.name,
                        data: s.data
                    })),
                    chart: { type: 'area', height: 260, stacked: true, toolbar: { show: false }, zoom: { enabled: false } },
                    colors: d.series.map(s => channelColors[s.name.toLowerCase()] || '#adb5bd'),
                    stroke: { curve: 'smooth', width: 1 },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.1 } },
                    xaxis: { categories: d.dates, labels: { rotate: -30, style: { fontSize: '11px' } } },
                    yaxis: { labels: { formatter: v => Math.round(v) } },
                    grid: { borderColor: '#f0f0f0' },
                    tooltip: { theme: 'light', shared: true },
                    legend: { position: 'top' }
                });
                channelChart.render();
            });
    }

    // ─── Hourly heatmap ──────────────────────────────────────────────────
    function loadHourlyHeatmap() {
        $.get(routes.hourlyHeatmap, { range: selectedRange })
            .done(function (res) {
                if (!res.success) { $('#heatmap-chart').html(emptyState('Sin datos')); return; }
                const { matrix, days } = res.data;
                if (!matrix) return;

                const series = days.map((day, dayIdx) => ({
                    name: day,
                    data: matrix[dayIdx].map((val, hour) => ({ x: String(hour).padStart(2, '0') + 'h', y: val }))
                }));

                if (heatmapChart) { heatmapChart.destroy(); heatmapChart = null; }
                $('#heatmap-chart').html('');

                heatmapChart = new ApexCharts(document.querySelector('#heatmap-chart'), {
                    series: series,
                    chart: { type: 'heatmap', height: 200, toolbar: { show: false } },
                    dataLabels: { enabled: false },
                    colors: ['#b10100'],
                    xaxis: { labels: { style: { fontSize: '10px' } } },
                    tooltip: { theme: 'light', y: { formatter: v => fmt(v) + ' sesiones' } },
                    legend: { show: false }
                });
                heatmapChart.render();
            });
    }

    // ─── Browsers ────────────────────────────────────────────────────────
    const browserChartColors = ['#b10100','#333333','#7b0000','#555555','#c41c1c','#888888'];
    const browserBgCls  = ['bg-primary-subtle','bg-primary-subtle','bg-primary-subtle','bg-primary-subtle','bg-primary-subtle','bg-primary-subtle'];
    const browserTxtCls = ['text-primary','text-primary','text-primary','text-primary','text-primary','text-primary'];
    const browserIconMap = { chrome:'fab fa-chrome', safari:'fab fa-safari', firefox:'fab fa-firefox-browser', edge:'fab fa-edge', opera:'fab fa-opera', samsung:'fas fa-mobile-alt' };

    function loadBrowsers() {
        $.get(routes.topBrowsers, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.length) { $('#browser-chart').html(emptyState('Sin datos')); return; }
                const data  = res.data;
                const total = data.reduce((s, b) => s + b.sessions, 0);

                if (browserChart) { browserChart.destroy(); browserChart = null; }
                $('#browser-chart').html('');

                browserChart = new ApexCharts(document.querySelector('#browser-chart'), {
                    series: data.map(b => b.sessions),
                    labels: data.map(b => b.name),
                    chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
                    colors: data.map((b, i) => browserChartColors[i % browserChartColors.length]),
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    tooltip: { y: { formatter: v => fmt(v) } },
                    plotOptions: { pie: { donut: { size: '75%' } } },
                });
                browserChart.render();

                $('#browser-list').html(data.slice(0, 5).map((b, i) => {
                    const key    = b.name.toLowerCase().replace(/\s+/g, '');
                    const bg     = browserBgCls[i % browserBgCls.length];
                    const txt    = browserTxtCls[i % browserTxtCls.length];
                    const icon   = browserIconMap[key] || 'fas fa-globe';
                    const pct    = total > 0 ? ((b.sessions / total) * 100).toFixed(1) : '0.0';
                    const isLast = i === Math.min(data.length, 5) - 1;
                    return `<div class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'}">
                        <div class="d-flex align-items-center">
                            <div class="p-2 ${bg} rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                                <i class="${icon} ${txt}"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">${escHtml(b.name)}</h6>
                                <p class="fs-3 mb-0 text-muted">${fmt(b.sessions)} sesiones</p>
                            </div>
                        </div>
                        <h6 class="mb-0 fw-semibold">${pct}% <small class="text-muted fw-normal">${fmt(b.sessions)}</small></h6>
                    </div>`;
                }).join(''));
            });
    }

    // ─── Map ─────────────────────────────────────────────────────────────
    const countryNameMap = {
        'United States': 'United States of America',
        'Czech Republic': 'Czechia',
        'Tanzania': 'United Republic of Tanzania',
        'Congo': 'Republic of the Congo',
    };

    // GA4 country name → flag emoji
    const countryFlags = {
        'Afghanistan':'🇦🇫','Albania':'🇦🇱','Algeria':'🇩🇿','Argentina':'🇦🇷','Armenia':'🇦🇲',
        'Australia':'🇦🇺','Austria':'🇦🇹','Azerbaijan':'🇦🇿','Bangladesh':'🇧🇩','Belarus':'🇧🇾',
        'Belgium':'🇧🇪','Bolivia':'🇧🇴','Brazil':'🇧🇷','Bulgaria':'🇧🇬','Cambodia':'🇰🇭',
        'Canada':'🇨🇦','Chile':'🇨🇱','China':'🇨🇳','Colombia':'🇨🇴','Costa Rica':'🇨🇷',
        'Croatia':'🇭🇷','Cuba':'🇨🇺','Czech Republic':'🇨🇿','Czechia':'🇨🇿','Denmark':'🇩🇰',
        'Dominican Republic':'🇩🇴','Ecuador':'🇪🇨','Egypt':'🇪🇬','El Salvador':'🇸🇻',
        'Estonia':'🇪🇪','Ethiopia':'🇪🇹','Finland':'🇫🇮','France':'🇫🇷','Georgia':'🇬🇪',
        'Germany':'🇩🇪','Ghana':'🇬🇭','Greece':'🇬🇷','Guatemala':'🇬🇹','Honduras':'🇭🇳',
        'Hungary':'🇭🇺','India':'🇮🇳','Indonesia':'🇮🇩','Iran':'🇮🇷','Iraq':'🇮🇶',
        'Ireland':'🇮🇪','Israel':'🇮🇱','Italy':'🇮🇹','Japan':'🇯🇵','Jordan':'🇯🇴',
        'Kazakhstan':'🇰🇿','Kenya':'🇰🇪','Kuwait':'🇰🇼','Latvia':'🇱🇻','Lebanon':'🇱🇧',
        'Libya':'🇱🇾','Lithuania':'🇱🇹','Malaysia':'🇲🇾','Mexico':'🇲🇽','Morocco':'🇲🇦',
        'Netherlands':'🇳🇱','New Zealand':'🇳🇿','Nicaragua':'🇳🇮','Nigeria':'🇳🇬','Norway':'🇳🇴',
        'Pakistan':'🇵🇰','Panama':'🇵🇦','Paraguay':'🇵🇾','Peru':'🇵🇪','Philippines':'🇵🇭',
        'Poland':'🇵🇱','Portugal':'🇵🇹','Puerto Rico':'🇵🇷','Romania':'🇷🇴','Russia':'🇷🇺',
        'Saudi Arabia':'🇸🇦','Senegal':'🇸🇳','Serbia':'🇷🇸','Singapore':'🇸🇬','Slovakia':'🇸🇰',
        'Slovenia':'🇸🇮','South Africa':'🇿🇦','South Korea':'🇰🇷','Spain':'🇪🇸','Sri Lanka':'🇱🇰',
        'Sweden':'🇸🇪','Switzerland':'🇨🇭','Syria':'🇸🇾','Taiwan':'🇹🇼','Tanzania':'🇹🇿',
        'Thailand':'🇹🇭','Tunisia':'🇹🇳','Turkey':'🇹🇷','Ukraine':'🇺🇦','United Arab Emirates':'🇦🇪',
        'United Kingdom':'🇬🇧','United States':'🇺🇸','Uruguay':'🇺🇾','Uzbekistan':'🇺🇿',
        'Venezuela':'🇻🇪','Vietnam':'🇻🇳','Yemen':'🇾🇪','Zimbabwe':'🇿🇼',
    };

    const countryLayers = {};

    function getCountryFlag(name) {
        return countryFlags[name] || '🌐';
    }

    function getColor(sessions, max) {
        if (!sessions) return '#f0f0f0';
        const r = sessions / max;
        if (r > 0.75) return '#7b0000';
        if (r > 0.5)  return '#b10100';
        if (r > 0.25) return '#c41c1c';
        if (r > 0.1)  return '#d94444';
        if (r > 0.02) return '#f0aaaa';
        return '#fce8e8';
    }

    function initMap() {
        if (leafletMap) { leafletMap.remove(); leafletMap = null; }
        leafletMap = L.map('visits-map', { zoomControl: false, scrollWheelZoom: false, attributionControl: false }).setView([20, 0], 1.5);
        L.control.zoom({ position: 'bottomright' }).addTo(leafletMap);

        $('#mapResetBtn').on('click', function () {
            leafletMap.flyTo([20, 0], 1.5, { duration: 0.8 });
            $('.country-item').removeClass('country-active');
        });
    }

    function zoomToCountry(apiName) {
        const geoName = countryNameMap[apiName] || apiName;
        const layer   = countryLayers[geoName] || countryLayers[apiName];
        if (!layer) { return; }
        leafletMap.flyToBounds(layer.getBounds(), { padding: [30, 30], duration: 0.9, maxZoom: 6 });
        $('.country-item').removeClass('country-active');
        $(`.country-item[data-country="${CSS.escape(apiName)}"]`).addClass('country-active');
    }

    function drawGeoLayer(geoJson, maxSessions) {
        if (geoLayer) { leafletMap.removeLayer(geoLayer); }
        Object.keys(countryLayers).forEach(k => delete countryLayers[k]);

        geoLayer = L.geoJson(geoJson, {
            style: feature => {
                const name = feature.properties.ADMIN || feature.properties.name;
                return { fillColor: getColor(countrySessions[name] || 0, maxSessions), weight: 0.5, color: '#fff', fillOpacity: 0.85 };
            },
            onEachFeature: (feature, layer) => {
                const name     = feature.properties.ADMIN || feature.properties.name;
                const sessions = countrySessions[name] || 0;

                countryLayers[name] = layer;

                const tooltipContent = sessions
                    ? `<strong>${name}</strong><br><span style="color:#b10100">${fmt(sessions)}</span> sesiones`
                    : `<strong>${name}</strong><br><span class="text-muted">Sin visitas</span>`;
                layer.bindTooltip(tooltipContent, { sticky: true, className: 'leaflet-tooltip-custom' });

                layer.on('mouseover', function () {
                    if (sessions) { this.setStyle({ weight: 2, color: '#b10100', fillOpacity: 1 }); }
                });
                layer.on('mouseout', function () {
                    geoLayer.resetStyle(this);
                });
                layer.on('click', function () {
                    if (!sessions) { return; }
                    leafletMap.flyToBounds(layer.getBounds(), { padding: [30, 30], duration: 0.9, maxZoom: 6 });
                    // Find original API name
                    const apiName = Object.keys(countryNameMap).find(k => countryNameMap[k] === name) || name;
                    $('.country-item').removeClass('country-active');
                    $(`.country-item[data-country="${CSS.escape(apiName)}"]`).addClass('country-active');
                    // Scroll list to item
                    const $item = $(`.country-item[data-country="${CSS.escape(apiName)}"]`);
                    if ($item.length) {
                        $('#countries-list').animate({ scrollTop: $item.offset().top - $('#countries-list').offset().top + $('#countries-list').scrollTop() }, 300);
                    }
                });
            }
        }).addTo(leafletMap);
    }

    function loadCountries() {
        $.get(routes.topCountries, { range: selectedRange })
            .done(function (res) {
                if (!res.success) return;
                exportCache['countries'] = res.data;
                countrySessions = {};
                res.data.forEach(d => {
                    countrySessions[countryNameMap[d.country] || d.country] = d.sessions;
                    countrySessions[d.country] = d.sessions;
                });
                const maxSessions = res.data.length ? Math.max(...res.data.map(d => d.sessions)) : 1;

                if (countriesGeoData) { drawGeoLayer(countriesGeoData, maxSessions); }
                else {
                    $.getJSON('{{ asset("vendor/leaflet/countries.geojson") }}')
                        .done(geoJson => { countriesGeoData = geoJson; drawGeoLayer(geoJson, maxSessions); })
                        .fail(() => $('#visits-map').html('<div class="text-center py-5 text-muted"><i class="fas fa-exclamation-triangle"></i> No se pudo cargar el mapa</div>'));
                }

                const rankColor = i => i === 0 ? '#b10100' : i === 1 ? '#c41c1c' : i === 2 ? '#d94444' : '#ccc';
                $('#countries-list').html(res.data.slice(0, 15).map((c, i) => `
                    <div class="country-item" data-country="${escHtml(c.country)}">
                        <span class="fw-bold flex-shrink-0 me-2" style="font-size:0.75rem;width:18px;text-align:right;color:${rankColor(i)};">${i + 1}</span>
                        <div style="min-width:0;flex:1;">
                            <div class="fw-semibold text-truncate mb-1" style="font-size:0.82rem;">${escHtml(c.country)}</div>
                            <div class="progress rounded-pill" style="height:6px;">
                                <div class="progress-bar rounded-pill" style="width:${c.percentage}%;background:#b10100;"></div>
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0 ms-3">
                            <div class="fw-bold lh-1" style="font-size:0.95rem;color:#b10100;">${fmt(c.sessions)}</div>
                            <div class="text-muted lh-1 mt-1" style="font-size:0.7rem;">${c.percentage}%</div>
                        </div>
                    </div>`).join(''));

                $(document).on('click', '.country-item', function () {
                    zoomToCountry($(this).data('country'));
                });
            });
    }

    // ─── Devices ─────────────────────────────────────────────────────────
    const deviceIcons  = { desktop: 'fas fa-desktop', mobile: 'fas fa-mobile-alt', tablet: 'fas fa-tablet-alt' };
    const deviceColors = { desktop: '#b10100', mobile: '#333333', tablet: '#7b0000' };
    const deviceBg     = { desktop: 'bg-primary-subtle', mobile: 'bg-primary-subtle', tablet: 'bg-primary-subtle' };
    const deviceTxt    = { desktop: 'text-primary',      mobile: 'text-primary',      tablet: 'text-primary'     };
    const deviceLabel  = { desktop: 'Computadoras de escritorio', mobile: 'Teléfonos móviles', tablet: 'Tablets y similares' };

    function loadDevices() {
        $.get(routes.deviceCategories, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.length) { $('#devices-chart').html(emptyState('Sin datos')); return; }
                const data = res.data;

                if (devicesChart) { devicesChart.destroy(); devicesChart = null; }
                $('#devices-chart').html('');
                devicesChart = new ApexCharts(document.querySelector('#devices-chart'), {
                    series: data.map(d => d.sessions),
                    labels: data.map(d => d.device),
                    chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
                    colors: data.map(d => deviceColors[d.device.toLowerCase()] || '#6c757d'),
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    tooltip: { y: { formatter: v => fmt(v) } },
                    plotOptions: { pie: { donut: { size: '75%' } } },
                });
                devicesChart.render();

                $('#devices-list').html(data.map((d, i) => {
                    const key   = d.device.toLowerCase();
                    const bg    = deviceBg[key]    || 'bg-secondary-subtle';
                    const txt   = deviceTxt[key]   || 'text-secondary';
                    const icon  = deviceIcons[key] || 'fas fa-question-circle';
                    const sub   = deviceLabel[key] || escHtml(d.device);
                    const isLast = i === data.length - 1;
                    return `<div class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'}">
                        <div class="d-flex align-items-center">
                            <div class="p-2 ${bg} rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                                <i class="${icon} ${txt}"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">${escHtml(d.device)}</h6>
                                <p class="fs-3 mb-0 text-muted">${sub}</p>
                            </div>
                        </div>
                        <h6 class="mb-0 fw-semibold">${fmt(d.sessions)} <small class="text-muted fw-normal">${d.percentage}%</small></h6>
                    </div>`;
                }).join(''));
            });
    }

    // ─── OS ──────────────────────────────────────────────────────────────
    const osColors  = ['#b10100','#333333','#7b0000','#555555','#c41c1c','#888888','#d94444','#000000','#f0aaaa','#f5f6f8'];
    const osBgCls   = ['bg-primary-subtle','bg-primary-subtle','bg-primary-subtle','bg-primary-subtle','bg-primary-subtle','bg-primary-subtle'];
    const osTxtCls  = ['text-primary','text-primary','text-primary','text-primary','text-primary','text-primary'];
    const osIconMap = { windows:'fab fa-windows', android:'fab fa-android', ios:'fab fa-apple', macos:'fab fa-apple', linux:'fab fa-linux', chrome:'fab fa-chrome' };

    function loadOS() {
        $.get(routes.operatingSystems, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.length) { $('#os-list').html(emptyState('Sin datos')); return; }
                $('#os-list').html(res.data.map((o, i) => {
                    const key    = o.os.toLowerCase().replace(/\s+/g, '');
                    const bg     = osBgCls[i % osBgCls.length];
                    const txt    = osTxtCls[i % osTxtCls.length];
                    const icon   = osIconMap[key] || 'fas fa-laptop';
                    const isLast = i === res.data.length - 1;
                    return `<div class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'}">
                        <div class="d-flex align-items-center">
                            <div class="p-2 ${bg} rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                                <i class="${icon} ${txt}"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">${escHtml(o.os)}</h6>
                                <p class="fs-3 mb-0 text-muted">${fmt(o.sessions)} sesiones</p>
                            </div>
                        </div>
                        <h6 class="mb-0 fw-semibold">${o.percentage}%</h6>
                    </div>`;
                }).join(''));
            });
    }

    // ─── Traffic sources ─────────────────────────────────────────────────
    function loadTrafficSources() {
        $.get(routes.trafficSources, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.length) { $('#traffic-sources-list').html(emptyState('Sin datos')); return; }
                exportCache['channels'] = res.data;
                setPagerData('trafficSources', res.data);
                pagerRenders['trafficSources'] = function () {
                    const rows = getPageItems('trafficSources').map((s, i) => {
                        const badge  = termBadges[i % termBadges.length];
                        const label  = s.source === '(direct)' ? 'Directo' : escHtml(s.source);
                        const med    = s.medium === 'none' || s.medium === '(none)' || !s.medium ? 'directo' : escHtml(s.medium);
                        return `<tr>
                            <td class="ps-0">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary">${(getPager('trafficSources').page - 1) * PER_PAGE + i + 1}</span>
                                    <div>
                                        <div class="fw-semibold small">${label}</div>
                                        <small class="text-muted">${med}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge fw-semibold py-1 ${badge}">${fmt(s.sessions)}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress progress-thin flex-fill" style="min-width:50px">
                                        <div class="progress-bar bg-info" style="width:${s.percentage}%"></div>
                                    </div>
                                    <small class="text-muted">${s.percentage}%</small>
                                </div>
                            </td>
                        </tr>`;
                    }).join('');
                    $('#traffic-sources-list').html(
                        `<table class="table align-middle text-nowrap mb-0">
                            <thead><tr class="text-muted fw-semibold">
                                <th scope="col" class="ps-0">Fuente</th>
                                <th scope="col">Sesiones</th>
                                <th scope="col">Participación</th>
                            </tr></thead>
                            <tbody class="border-top">${rows}</tbody>
                        </table>`
                    );
                    $('#traffic-sources-pager').html(pagerNav('trafficSources'));
                };
                pagerRenders['trafficSources']();
            })
            .fail(function () { $('#traffic-sources-list').html(emptyState('Sin datos')); });
    }

    // ─── Pages (top / landing / exit) ────────────────────────────────────
    function pagesTable(rows, cols, headers) {
        const thead = `<thead><tr class="text-muted fw-semibold">${headers.map((h, i) => `<th scope="col" class="${i === 0 ? 'ps-0' : ''}${h.end ? ' text-end' : ''}">${h.label}</th>`).join('')}</tr></thead>`;
        const body  = rows.map(r => '<tr>' + cols.map((c, i) => `<td class="${i === 0 ? 'ps-0' : ''}${c.end ? ' text-end' : ''}">${c.render(r)}</td>`).join('') + '</tr>').join('');
        return `<table class="table align-middle mb-0 text-nowrap">${thead}<tbody class="border-top">${body}</tbody></table>`;
    }

    function loadTopPages() {
        $.get(routes.topPages, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.length) { $('#pages-list').html(emptyState('Sin datos')); return; }
                exportCache['pages'] = res.data;
                setPagerData('topPages', res.data);
                pagerRenders['topPages'] = function () {
                    $('#pages-list').html(pagesTable(
                        getPageItems('topPages'),
                        [
                            { render: p => `<h6 class="mb-0 fw-semibold  text-truncate" style="max-width:300px">${escHtml(p.title)}</h6><span class="fs-2 text-muted text-truncate d-block" style="max-width:300px">${escHtml(p.url)}</span>` },
                            { end: true, render: p => `<span class="badge bg-primary-subtle text-primary rounded-pill">${fmt(p.views)}</span>` },
                        ],
                        [{ label: 'Página' }, { label: 'Vistas', end: true }]
                    ));
                    $('#pages-pager').html(pagerNav('topPages'));
                };
                pagerRenders['topPages']();
            });
    }

    function loadLandingPages() {
        $.get(routes.landingPages, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.length) { $('#landing-pages-list').html(emptyState('Sin datos')); return; }
                setPagerData('landingPages', res.data);
                pagerRenders['landingPages'] = function () {
                    $('#landing-pages-list').html(pagesTable(
                        getPageItems('landingPages'),
                        [
                            { render: p => `<h6 class="mb-0 fw-semibold small text-truncate" style="max-width:300px">${escHtml(p.page)}</h6>` },
                            { end: true, render: p => `<span class="badge bg-success-subtle text-success rounded-pill">${fmt(p.sessions)}</span>` },
                            { end: true, render: p => `<small class="text-muted">${p.bounce_rate}%</small>` },
                        ],
                        [{ label: 'Página de entrada' }, { label: 'Sesiones', end: true }, { label: 'Rebote', end: true }]
                    ));
                    $('#landing-pages-pager').html(pagerNav('landingPages'));
                };
                pagerRenders['landingPages']();
            });
    }

    function loadExitPages() {
        $.get(routes.exitPages, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.length) { $('#exit-pages-list').html(emptyState('Sin datos')); return; }
                setPagerData('exitPages', res.data);
                pagerRenders['exitPages'] = function () {
                    $('#exit-pages-list').html(pagesTable(
                        getPageItems('exitPages'),
                        [
                            { render: p => `<h6 class="mb-0 fw-semibold small text-truncate" style="max-width:300px">${escHtml(p.page)}</h6>` },
                            { end: true, render: p => `<span class="badge bg-warning-subtle text-warning rounded-pill">${fmt(p.sessions)}</span>` },
                            { end: true, render: p => `<small class="text-muted">${fmt(p.pageviews)}</small>` },
                        ],
                        [{ label: 'Página de salida' }, { label: 'Sesiones', end: true }, { label: 'Vistas', end: true }]
                    ));
                    $('#exit-pages-pager').html(pagerNav('exitPages'));
                };
                pagerRenders['exitPages']();
            });
    }

    // ─── Referrers ───────────────────────────────────────────────────────
    const mediumBg    = { organic:'bg-success-subtle', cpc:'bg-primary-subtle', social:'bg-info-subtle', referral:'bg-warning-subtle', email:'bg-danger-subtle', direct:'bg-secondary-subtle', none:'bg-secondary-subtle' };
    const mediumColor = { organic:'text-success', cpc:'text-primary', social:'text-info', referral:'text-warning', email:'text-danger', direct:'text-secondary', none:'text-secondary' };
    const mediumLabel = { organic:'Búsqueda orgánica', cpc:'Publicidad CPC', social:'Redes sociales', referral:'Referencia', email:'Email marketing', direct:'Acceso directo', none:'Acceso directo' };
    const mediumIcons = { organic:'fas fa-search', cpc:'fas fa-ad', social:'fas fa-share-alt', referral:'fas fa-link', email:'fas fa-envelope', direct:'fas fa-arrow-right', none:'fas fa-arrow-right' };

    function loadReferrers() {
        $.get(routes.topReferrers, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.length) { $('#referrers-list').html(emptyState('Sin datos')); return; }
                setPagerData('referrers', res.data);
                pagerRenders['referrers'] = function () {
                    const items = getPageItems('referrers').map((r, i) => {
                        const medKey = (r.medium || 'direct').toLowerCase();
                        const bg     = mediumBg[medKey]    || 'bg-secondary-subtle';
                        const color  = mediumColor[medKey] || 'text-secondary';
                        const icon   = mediumIcons[medKey] || 'fas fa-globe';
                        const label  = r.source === '(direct)' ? 'Directo' : escHtml(r.source);
                        const sub    = mediumLabel[medKey]  || escHtml(r.medium || 'directo');
                        const isLast = i === getPageItems('referrers').length - 1;
                        return `<div class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'}">
                            <div class="d-flex align-items-center">
                                <div class="p-2 ${bg} rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                                    <i class="${icon} ${color}"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">${label}</h6>
                                    <p class="fs-3 mb-0 text-muted">${sub}</p>
                                </div>
                            </div>
                            <h6 class="mb-0 fw-semibold">${fmt(r.views)}</h6>
                        </div>`;
                    }).join('');
                    $('#referrers-list').html(items);
                    $('#referrers-pager').html(pagerNav('referrers'));
                };
                pagerRenders['referrers']();
            });
    }

    // ─── Search Terms ─────────────────────────────────────────────────────
    const termBadges = ['bg-primary-subtle text-primary','bg-success-subtle text-success','bg-warning-subtle text-warning','bg-info-subtle text-info','bg-danger-subtle text-danger'];

    // ─── Pagination ──────────────────────────────────────────────────────────
    const PER_PAGE = 10;
    const pagerState = {};

    function getPager(key) {
        if (!pagerState[key]) { pagerState[key] = { data: [], page: 1 }; }
        return pagerState[key];
    }

    function setPagerData(key, data) {
        getPager(key).data = data;
        getPager(key).page = 1;
    }

    function getPageItems(key) {
        const s = getPager(key);
        return s.data.slice((s.page - 1) * PER_PAGE, s.page * PER_PAGE);
    }

    function pagerNav(key) {
        const s = getPager(key);
        const total = s.data.length;
        const totalPages = Math.ceil(total / PER_PAGE);
        if (totalPages <= 1) return '';
        const page  = s.page;
        const from  = (page - 1) * PER_PAGE + 1;
        const to    = Math.min(page * PER_PAGE, total);
        const start = Math.max(1, page - 2);
        const end   = Math.min(totalPages, start + 4);

        const btn = (p, label, disabled, active) => {
            const base  = 'pager-btn d-inline-flex align-items-center justify-content-center border-0 rounded';
            const style = active
                ? `background:#b10100;color:#fff;font-weight:600;`
                : (disabled ? 'background:transparent;color:#ccc;cursor:default;' : 'background:transparent;color:#555;');
            return `<button class="${base}" style="width:30px;height:30px;font-size:0.8rem;${style}"` +
                   ` data-pkey="${key}" data-page="${p}" ${disabled ? 'disabled' : ''}>${label}</button>`;
        };

        let btns = btn(page - 1, '&#8249;', page <= 1, false);
        for (let i = start; i <= end; i++) { btns += btn(i, i, false, i === page); }
        btns += btn(page + 1, '&#8250;', page >= totalPages, false);

        return `<div class="d-flex align-items-center justify-content-between pt-1">` +
               `<small class="text-muted">${from}–${to} de ${total}</small>` +
               `<div class="d-flex align-items-center gap-1">${btns}</div></div>`;
    }

    function loadSearchTerms() {
        $.get(routes.searchTerms, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.length) {
                    $('#search-terms-list').html(emptyState('Sin datos de búsquedas'));
                    return;
                }
                const total = res.data.reduce((s, t) => s + t.sessions, 0);
                setPagerData('searchTerms', res.data);
                pagerRenders['searchTerms'] = function () {
                    const rows = getPageItems('searchTerms').map((t, i) => {
                        const pct   = total > 0 ? ((t.sessions / total) * 100).toFixed(1) : 0;
                        const badge = termBadges[i % termBadges.length];
                        const num   = (getPager('searchTerms').page - 1) * PER_PAGE + i + 1;
                        return `<tr>
                            <td class="ps-0">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary">${num}</span>
                                    <span class="fw-semibold">${escHtml(t.term)}</span>
                                </div>
                            </td>
                            <td><span class="badge fw-semibold py-1 ${badge}">${fmt(t.sessions)}</span></td>
                            <td><small class="text-muted">${fmt(t.pageviews)}</small></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress progress-thin flex-fill" style="min-width:50px">
                                        <div class="progress-bar bg-info" style="width:${pct}%"></div>
                                    </div>
                                    <small class="text-muted">${pct}%</small>
                                </div>
                            </td>
                        </tr>`;
                    }).join('');
                    $('#search-terms-list').html(
                        `<table class="table align-middle text-nowrap mb-0">
                            <thead><tr class="text-muted fw-semibold">
                                <th scope="col" class="ps-0">Término</th>
                                <th scope="col">Sesiones</th>
                                <th scope="col">Vistas</th>
                                <th scope="col">Participación</th>
                            </tr></thead>
                            <tbody class="border-top">${rows}</tbody>
                        </table>`
                    );
                    $('#search-terms-pager').html(pagerNav('searchTerms'));
                };
                pagerRenders['searchTerms']();
            })
            .fail(function () { $('#search-terms-list').html(emptyState('Sin datos')); });
    }

    // ─── User Flow ────────────────────────────────────────────────────────
    function loadUserFlow() {
        $.get(routes.userFlow, { range: selectedRange })
            .done(function (res) {
                if (!res.success || !res.data.length) {
                    $('#user-flow-list').html(emptyState('Sin datos de flujo'));
                    return;
                }
                setPagerData('userFlow', res.data);
                pagerRenders['userFlow'] = function () {
                    const rows = getPageItems('userFlow').map((r, i) => {
                        const badge = termBadges[i % termBadges.length];
                        return `<tr>
                            <td class="ps-0 text-truncate" style="max-width:180px">
                                <div class="small text-truncate">${escHtml(r.landing)}</div>
                            </td>
                            <td class="text-truncate" style="max-width:180px">
                                <div class="small text-truncate">${escHtml(r.exit)}</div>
                            </td>
                            <td><span class="badge fw-semibold py-1 ${badge}">${fmt(r.sessions)}</span></td>
                            <td><small class="text-muted">${r.bounce_rate}%</small></td>
                        </tr>`;
                    }).join('');
                    $('#user-flow-list').html(
                        `<table class="table align-middle text-nowrap mb-0">
                            <thead><tr class="text-muted fw-semibold">
                                <th scope="col" class="ps-0">Entrada</th>
                                <th scope="col">Salida</th>
                                <th scope="col">Sesiones</th>
                                <th scope="col">Rebote</th>
                            </tr></thead>
                            <tbody class="border-top">${rows}</tbody>
                        </table>`
                    );
                    $('#user-flow-pager').html(pagerNav('userFlow'));
                };
                pagerRenders['userFlow']();
            })
            .fail(function () { $('#user-flow-list').html(emptyState('Sin datos')); });
    }

    // ─── Export ──────────────────────────────────────────────────────────
    function convertToCSV(data) {
        if (!data || !data.length) return '';
        const rows = Array.isArray(data) ? data : (data.rows || data.data || []);
        if (!rows.length) return '';
        const headers = Object.keys(rows[0]);
        const lines = [
            headers.join(','),
            ...rows.map(row => headers.map(h => {
                const val = row[h] ?? '';
                const str = String(val).replace(/"/g, '""');
                return str.includes(',') || str.includes('"') || str.includes('\n') ? `"${str}"` : str;
            }).join(','))
        ];
        return lines.join('\r\n');
    }

    window.exportData = function (type, format = 'json') {
        const data = exportCache[type];
        if (!data) { alert('Primero carga los datos del dashboard.'); return; }

        let blob, filename;

        if (format === 'csv') {
            const csv = convertToCSV(data);
            blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            filename = `analytics_${type}_${selectedRange}_${new Date().toISOString().slice(0,10)}.csv`;
        } else {
            blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            filename = `analytics_${type}_${selectedRange}_${new Date().toISOString().slice(0,10)}.json`;
        }

        const url = URL.createObjectURL(blob);
        const a   = document.createElement('a');
        a.href     = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    };

    // ─── Load all ────────────────────────────────────────────────────────
    function loadAll() {
        currentTotals = null;
        $('#kpi-sessions,#kpi-users,#kpi-pageviews,#kpi-bounce,#kpi-new-users,#kpi-duration')
            .html('<span class="spinner-border spinner-border-sm"></span>');
        $('#cmp-sessions,#cmp-users,#cmp-pageviews,#cmp-bounce').html('');
        $('#browser-list,#countries-list,#devices-list,#os-list,#traffic-sources-list,#pages-list,#landing-pages-list,#exit-pages-list,#referrers-list,#search-terms-list,#user-flow-list')
            .html(spinner());
        $('#export-period-label').text($('#rangePills .nav-link.active').text().trim());

        loadOverview();
        loadSessionMetrics();
        loadBrowsers();
        loadCountries();
        loadDevices();
        loadOS();
        loadTrafficSources();
        loadTopPages();
        loadLandingPages();
        loadExitPages();
        loadReferrers();
        loadChannelTrend();
        loadHourlyHeatmap();
        loadSearchTerms();
        loadUserFlow();
    }

    // Pager click handler
    const pagerRenders = {};

    $(document).on('click', '.pager-btn', function (e) {
        e.preventDefault();
        const key  = $(this).data('pkey');
        const page = parseInt($(this).data('page'));
        if (!pagerState[key] || pagerState[key].page === page) { return; }
        pagerState[key].page = page;
        pagerRenders[key]();
    });

    // ─── Events ──────────────────────────────────────────────────────────
    $(document).on('click', '#rangePills .nav-link', function (e) {
        e.preventDefault();
        $('#rangePills .nav-link').removeClass('active');
        $(this).addClass('active');
        selectedRange = $(this).data('range');
        loadAll();
    });

    $('#refreshBtn2').on('click', loadAll);

    // Realtime: cada 30 segundos
    loadRealtime();
    setInterval(loadRealtime, 30000);

    // Init
    $(document).ready(function () {
        initMap();
        loadAll();
    });
})();
</script>
@endpush
