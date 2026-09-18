/*!
 * Helpdesk · reporte "CSAT" (helpdesk/reports/csat.blade.php).
 *
 * Extraido del <script> inline de la vista para que el navegador lo cachee
 * en vez de re-descargarlo en cada carga de la página. La config dinámica
 * (URLs de datos y exportación) llega por window.HdReportsCsat, inyectada
 * por Blade justo antes de este <script>.
 */
(function () {
    'use strict';

    var cfg = window.HdReportsCsat || {};
    var dataUrl = cfg.dataUrl;
    var exportUrl = cfg.exportUrl;

    // Escala de gris a verde de marca: de peor (1 estrella) a mejor (5 estrellas),
    // sin rojos ni colores fuera de la paleta de la casa.
    var RATING_COLORS = ['#333333', '#555555', '#4f6b0a', '#7d9f10', '#90bb13'];

    var distChart = null, trendChart = null, agentsChart = null;

    function fmt(n) { return new Intl.NumberFormat('es-ES').format(n); }
    function emptyState(msg) { return '<div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i><small>' + msg + '</small></div>'; }

    function renderStars(rating) {
        var html = '';
        for (var i = 1; i <= 5; i++) {
            html += '<i class="fas fa-star' + (i <= rating ? '' : ' text-muted opacity-25') + ' text-warning me-1"></i>';
        }
        return html;
    }

    function escHtml(t) {
        var d = document.createElement('div');
        d.textContent = t == null ? '' : t;
        return d.innerHTML;
    }

    function updateExportLink() {
        var from = $('#filter-from').val();
        var to = $('#filter-to').val();
        var qs = new URLSearchParams({ date_from: from, date_to: to }).toString();
        $('#btn-export').attr('href', exportUrl + '?' + qs);
    }

    function renderDistribution(distribution) {
        if (distChart) { distChart.destroy(); distChart = null; }
        $('#chart-distribution').html('');

        var series = [1, 2, 3, 4, 5].map(function (i) { return distribution[i] || 0; });
        if (series.every(function (v) { return v === 0; })) {
            $('#chart-distribution').html(emptyState('Sin valoraciones en el período'));
            return;
        }

        distChart = new ApexCharts(document.querySelector('#chart-distribution'), {
            series: series,
            labels: ['1 estrella', '2 estrellas', '3 estrellas', '4 estrellas', '5 estrellas'],
            chart: { type: 'donut', height: 260, fontFamily: 'inherit' },
            colors: RATING_COLORS,
            legend: { position: 'bottom', fontFamily: 'inherit' },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: function (v) { return fmt(v); } } },
            plotOptions: { pie: { donut: { size: '70%' } } },
        });
        distChart.render();
    }

    function renderTrend(trend) {
        if (trendChart) { trendChart.destroy(); trendChart = null; }
        $('#chart-trend').html('');

        if (!trend.length) {
            $('#chart-trend').html(emptyState('Sin datos de tendencia'));
            return;
        }

        trendChart = new ApexCharts(document.querySelector('#chart-trend'), {
            series: [{ name: 'Promedio', data: trend.map(function (r) { return r.avg; }) }],
            chart: { type: 'area', height: 260, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
            colors: ['#90bb13'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] } },
            xaxis: { categories: trend.map(function (r) { return r.day; }), labels: { style: { fontSize: '11px', colors: '#adb5bd' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { min: 1, max: 5, tickAmount: 4, labels: { style: { fontSize: '11px', colors: '#adb5bd' } } },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            tooltip: { theme: 'light' },
            markers: { size: 0 },
        });
        trendChart.render();
    }

    function renderAgents(byAgent) {
        if (agentsChart) { agentsChart.destroy(); agentsChart = null; }
        $('#chart-agents').html('');

        var topAgents = byAgent.slice(0, 8);
        if (!topAgents.length) {
            $('#chart-agents').html(emptyState('Sin datos de agentes'));
            return;
        }

        agentsChart = new ApexCharts(document.querySelector('#chart-agents'), {
            series: [{ name: 'Promedio CSAT', data: topAgents.map(function (a) { return a.avg; }) }],
            chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
            colors: ['#90bb13'],
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
            xaxis: { categories: topAgents.map(function (a) { return a.agent_name; }), min: 0, max: 5, tickAmount: 5, labels: { style: { fontSize: '11px', colors: '#adb5bd' } } },
            yaxis: { labels: { style: { fontSize: '12px', colors: '#555555' } } },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            dataLabels: { enabled: true, style: { colors: ['#ffffff'] } },
            tooltip: { theme: 'light' },
        });
        agentsChart.render();
    }

    function load(showToast) {
        var params = {
            date_from: $('#filter-from').val() || '',
            date_to: $('#filter-to').val() || '',
        };

        updateExportLink();

        var $icon = $('#btn-apply i');
        $icon.addClass('fa-spin');

        $.get(dataUrl, params).done(function (data) {
            var summary = data.summary;
            var distribution = data.distribution;
            var trend = data.trend;
            var byAgent = data.by_agent;
            var lowRatings = data.low_ratings;

            // KPIs
            $('#metric-avg').text(summary.avg !== null ? summary.avg + ' ★' : '—');
            $('#metric-total').text(fmt(summary.total));

            var promoters = (distribution[4] || 0) + (distribution[5] || 0);
            var detractors = (distribution[1] || 0) + (distribution[2] || 0);
            var nps = summary.total > 0
                ? Math.round(((promoters - detractors) / summary.total) * 100)
                : 0;
            $('#metric-nps').html(
                '<span class="' + (nps >= 0 ? 'text-success' : 'text-danger') + '">' +
                (nps >= 0 ? '+' : '') + nps + '%</span>'
            );

            if (trend.length >= 2) {
                var mid = Math.floor(trend.length / 2);
                var firstHalfAvg = trend.slice(0, mid).reduce(function (s, r) { return s + r.avg; }, 0) / mid;
                var secondHalfAvg = trend.slice(mid).reduce(function (s, r) { return s + r.avg; }, 0) / (trend.length - mid);
                var diff = +(secondHalfAvg - firstHalfAvg).toFixed(2);
                var arrow = diff >= 0
                    ? '<i class="fas fa-arrow-up text-success me-1"></i>' + diff
                    : '<i class="fas fa-arrow-down text-danger me-1"></i>' + diff;
                $('#metric-trend').html(arrow);
            } else {
                $('#metric-trend').text('—');
            }

            renderDistribution(distribution);
            renderTrend(trend);
            renderAgents(byAgent);

            // Low ratings table
            var tbody = $('#tbody-low-ratings');
            tbody.empty();
            if (lowRatings.length === 0) {
                tbody.html('<tr><td colspan="4" class="text-center text-muted py-4">Sin valoraciones bajas en el período.</td></tr>');
            } else {
                lowRatings.forEach(function (row) {
                    var date = row.answered_at ? new Date(row.answered_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
                    tbody.append(
                        '<tr>' +
                        '<td>' + escHtml(row.agent_name) + '</td>' +
                        '<td>' + renderStars(row.rating) + '</td>' +
                        '<td class="text-muted">' + escHtml(row.comment || '—') + '</td>' +
                        '<td class="text-nowrap text-muted small">' + date + '</td>' +
                        '</tr>'
                    );
                });
            }

            if (showToast) { toastr.success('Reporte CSAT actualizado'); }
        }).fail(function () {
            $('.kpi-value').text('—');
            toastr.error('Error al cargar los datos del reporte CSAT.');
        }).always(function () {
            $icon.removeClass('fa-spin');
        });
    }

    // Default date range: last 30 days
    var today = new Date();
    var prior = new Date(today);
    prior.setDate(prior.getDate() - 30);
    $('#filter-to').val(today.toISOString().slice(0, 10));
    $('#filter-from').val(prior.toISOString().slice(0, 10));

    $('#btn-apply').on('click', function () { load(true); });

    load(false);
}());
