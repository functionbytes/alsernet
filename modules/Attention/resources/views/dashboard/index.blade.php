@extends('layouts.theme')

@section('title', 'Dashboard')

@section('content')

    @include('core::components.card', ['title' => 'Dashboard'])

    <div class="widget-content">

        @include('attention::dashboard.partials._filters-bar')

        @include('attention::dashboard.partials._kpi-cards')

        @include('attention::dashboard.partials._charts-row')

        @include('attention::dashboard.partials._tables-row')

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
(function () {
    'use strict';

    let chartStatus = null, chartTrend = null;
    let sparkCharts = {};

    const routes = {
        chartData: '{{ route('attention.dashboard.chart-data') }}',
    };

    let currentFrom = '{{ $from->format('Y-m-d') }}';
    let currentTo   = '{{ $to->format('Y-m-d') }}';

    // ─── Helpers ──────────────────────────────────────────────────────────
    const fmt        = n   => new Intl.NumberFormat('es-ES').format(n);
    const escHtml    = t   => { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; };
    const spinner    = ()  => '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';
    const emptyState = msg => `<div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i><small>${escHtml(msg)}</small></div>`;

    const months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    function fmtDate(iso) {
        if (!iso || iso.length < 10) return iso;
        const d = new Date(iso + 'T00:00:00');
        return months[d.getMonth()] + ' ' + d.getDate();
    }

    function cmpBadge(current, previous, invert) {
        if (!previous || previous === 0) return '<p class="fs-3 mb-0 text-muted">sin datos anteriores</p>';
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

    // ─── Sparklines (igual a analytics) ───────────────────────────────────
    function renderSparklines(data) {
        const sparkCfg = (series, color, type) => ({
            series: [{ data: series }],
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
            '#spark-total':      { series: (data.spark_total      || []).map(r => parseInt(r.count || 0)), color: '#b10100', type: 'area' },
            '#spark-pending':    { series: (data.spark_pending     || []).map(r => parseInt(r.count || 0)), color: '#333333', type: 'bar'  },
            '#spark-in-process': { series: (data.spark_in_process  || []).map(r => parseInt(r.count || 0)), color: '#7b0000', type: 'bar'  },
        };

        Object.entries(sparks).forEach(([sel, cfg]) => {
            const el = document.querySelector(sel);
            if (!el) return;
            if (sparkCharts[sel]) { sparkCharts[sel].destroy(); }
            sparkCharts[sel] = new ApexCharts(el, sparkCfg(cfg.series, cfg.color, cfg.type));
            sparkCharts[sel].render();
        });
    }

    // ─── Trend chart — igual a "Sesiones y vistas de página" de analytics ──
    function renderTrend(data) {
        const el = document.querySelector('#chart-trend-7days');
        if (!el) return;
        if (!data || data.length === 0) { el.innerHTML = emptyState('Sin datos en el período'); return; }
        if (chartTrend) { chartTrend.destroy(); }
        el.innerHTML = '';

        const categories = data.map(r => fmtDate(r.date));
        const counts     = data.map(r => parseInt(r.count ?? 0));

        chartTrend = new ApexCharts(el, {
            series: [{ name: 'peticiones', data: counts }],
            chart: {
                type: 'area',
                height: 295,
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: 'inherit',
            },
            colors: ['#b10100'],
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] },
            },
            dataLabels: {
                enabled: true,
                style: { fontSize: '11px', fontWeight: 600 },
                background: { enabled: true, borderRadius: 3, borderWidth: 0, opacity: 0.85 },
            },
            markers: { size: 4 },
            xaxis: {
                categories,
                labels: { style: { fontSize: '11px', colors: '#adb5bd' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: { style: { fontSize: '11px', colors: '#adb5bd' }, formatter: v => Math.round(v) },
            },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            tooltip: { theme: 'light', shared: true, intersect: false },
            legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'inherit' },
        });
        chartTrend.render();
    }

    // ─── Donut chart (by status) ───────────────────────────────────────────
    function renderStatusDonut(data) {
        const el = document.querySelector('#chart-by-status');
        if (!el) return;
        if (!data || data.length === 0) { el.innerHTML = emptyState('Sin datos en el período'); return; }
        if (chartStatus) { chartStatus.destroy(); }
        el.innerHTML = '';
        chartStatus = new ApexCharts(el, {
            series: data.map(r => parseInt(r.count ?? 0)),
            labels: data.map(r => r.label ?? '—'),
            chart: { type: 'donut', height: 260, toolbar: { show: false }, fontFamily: 'inherit' },
            colors: ['#b10100', '#333333', '#7b0000', '#555555', '#c41c1c', '#888888'],
            legend: { position: 'bottom', fontFamily: 'inherit' },
            dataLabels: { enabled: true, formatter: (val, opts) => opts.w.globals.series[opts.seriesIndex] },
            tooltip: { theme: 'light' },
            plotOptions: { pie: { donut: { size: '65%' } } },
        });
        chartStatus.render();
    }

    // ─── OS-style list (by type / by department) — paleta analytics ──────
    const listBg    = ['#e8e8e8'];
    const listColor = ['#333333'];
    const listIcons = ['fas fa-tag', 'fas fa-folder', 'fas fa-file-alt', 'fas fa-layer-group', 'fas fa-circle', 'fas fa-square'];

    function renderOsList(selector, data, labelKey, valueLabel) {
        const el = document.querySelector(selector);
        if (!el) return;
        if (!data || data.length === 0) { el.innerHTML = emptyState('Sin datos en el período'); return; }
        const total = data.reduce((s, r) => s + parseInt(r.count ?? 0), 0);
        el.innerHTML = data.map((r, i) => {
            const bg     = listBg[i % listBg.length];
            const color  = listColor[i % listColor.length];
            const icon   = listIcons[i % listIcons.length];
            const count  = parseInt(r.count ?? 0);
            const pct    = total > 0 ? ((count / total) * 100).toFixed(1) : '0.0';
            const isLast = i === data.length - 1;
            return `<div class="d-flex align-items-center justify-content-between ${isLast ? '' : 'mb-4'}">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;background:${bg};">
                        <i class="${icon}" style="color:${color};"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold">${escHtml(r[labelKey] ?? '—')}</h6>
                        <p class="fs-3 mb-0 text-muted">${fmt(count)} ${escHtml(valueLabel)}</p>
                    </div>
                </div>
                <h6 class="mb-0 fw-semibold">${pct}%</h6>
            </div>`;
        }).join('');
    }

    // ─── Top categorías — Páginas style ───────────────────────────────────
    function renderCategories(data) {
        const el = document.querySelector('#categories-list');
        if (!el) return;
        if (!data || data.length === 0) { el.innerHTML = emptyState('Sin datos en el período'); return; }
        const rows = data.map((cat, i) => {
            const name  = cat.category ?? cat.name ?? '—';
            const count = cat.count ?? 0;
            return `<tr>
                <td class="ps-0">
                    <span class="badge bg-secondary-subtle text-secondary">${i + 1}</span>
                </td>
                <td class="ps-0">
                    <h6 class="mb-0 fw-semibold">${escHtml(name)}</h6>
                </td>
                <td class="text-end">
                    <span class="badge rounded-pill" style="background:#fce8e8;color:#b10100;">${fmt(count)}</span>
                </td>
            </tr>`;
        }).join('');
        el.innerHTML = `<table class="table align-middle mb-0">
            <thead>
                <tr class="text-muted fw-semibold">
                    <th scope="col" class="ps-0" style="width:40px;">#</th>
                    <th scope="col" class="ps-0">Categoría</th>
                    <th scope="col" class="text-end">Total</th>
                </tr>
            </thead>
            <tbody class="border-top">${rows}</tbody>
        </table>`;
    }

    // ─── Load all chart data ───────────────────────────────────────────────
    function loadChartData() {
        $('#chart-by-status, #chart-trend-7days').html(spinner());
        $('#list-by-type, #list-by-department, #categories-list').html(spinner());
        $('#kpi-total, #kpi-pending, #kpi-in-process, #kpi-avg-resolution, #kpi-sla, #kpi-satisfaction')
            .html('<span class="spinner-border spinner-border-sm text-muted"></span>');
        $('#cmp-total, #cmp-pending, #cmp-in-process').html('');

        $.get(routes.chartData, { from: currentFrom, to: currentTo })
            .done(function (data) {
                // ── KPI row 1 ──
                $('#kpi-total').text(fmt(data.total ?? 0));
                $('#kpi-pending').text(fmt(data.pending ?? 0));
                $('#kpi-in-process').text(fmt(data.in_process ?? 0));
                $('#cmp-total').html(cmpBadge(data.total, data.prev_total));
                $('#cmp-pending').html(cmpBadge(data.pending, data.prev_pending));
                $('#cmp-in-process').html(cmpBadge(data.in_process, data.prev_in_process));

                // ── KPI row 2 ──
                const avgH = parseFloat(data.avg_resolution_hours ?? 0);
                $('#kpi-avg-resolution').text(avgH > 0 ? avgH.toFixed(1) + 'h' : '—');

                const sla = parseFloat(data.sla_compliance ?? 0);
                $('#kpi-sla').text(sla > 0 ? sla.toFixed(1) + '%' : '—');

                const sat = parseFloat(data.avg_satisfaction ?? 0);
                $('#kpi-satisfaction').text(sat > 0 ? sat.toFixed(1) + ' / 5' : '—');

                // ── Sparklines ──
                renderSparklines(data);

                // ── Charts ──
                renderTrend(data.trend_7days);
                renderStatusDonut(data.by_status);
                renderOsList('#list-by-type',       data.by_type,       'label', 'peticiones');
                renderOsList('#list-by-department',  data.by_department, 'label', 'peticiones');

                // ── Top categorías ──
                renderCategories(data.top_categories);
            })
            .fail(function () {
                toastr.error('Error al cargar datos del dashboard');
                $('#chart-by-status, #chart-trend-7days, #list-by-type, #list-by-department, #categories-list')
                    .html(emptyState('Error al cargar datos'));
                $('#kpi-total, #kpi-pending, #kpi-in-process, #kpi-avg-resolution, #kpi-sla, #kpi-satisfaction').text('—');
            });
    }

    // ─── Init ──────────────────────────────────────────────────────────────
    $(document).ready(function () {
        loadChartData();

        // Daterange picker
        $('#filter-daterange').daterangepicker({
            startDate: moment($('#filter-from').val(), 'YYYY-MM-DD'),
            endDate:   moment($('#filter-to').val(),   'YYYY-MM-DD'),
            locale: {
                cancelLabel:  'Limpiar',
                applyLabel:   'Aplicar',
                format:       'DD/MM/YYYY',
                separator:    ' - ',
                daysOfWeek:   ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
                monthNames:   ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
                firstDay:     1,
            },
            ranges: {
                'Hoy':             [moment(), moment()],
                'Ayer':            [moment().subtract(1,'days'), moment().subtract(1,'days')],
                'Últimos 7 días':  [moment().subtract(6,'days'), moment()],
                'Últimos 30 días': [moment().subtract(29,'days'), moment()],
                'Este mes':        [moment().startOf('month'), moment().endOf('month')],
                'Mes anterior':    [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')],
                'Este año':        [moment().startOf('year'), moment()],
            },
        });

        $('#filter-daterange').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            $('#filter-from').val(picker.startDate.format('YYYY-MM-DD'));
            $('#filter-to').val(picker.endDate.format('YYYY-MM-DD'));
            $('#rangePills .nav-link').removeClass('active');
            $('#dashboard-filter-form').submit();
        });

        $('#filter-daterange-icon').on('click', function () {
            $('#filter-daterange').trigger('click');
        });

        // Nav-pills: quick period shortcuts
        $('#rangePills .nav-link').on('click', function (e) {
            e.preventDefault();
            $('#rangePills .nav-link').removeClass('active');
            $(this).addClass('active');
            $('#filter-from').val($(this).data('from'));
            $('#filter-to').val($(this).data('to'));
            $('#dashboard-filter-form').submit();
        });

        // Refresh button
        $('#refreshBtn').on('click', function () {
            loadChartData();
            toastr.info('Actualizando datos...');
        });
    });
}());
</script>
@endpush
