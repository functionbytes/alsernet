/*!
 * Helpdesk · reporte "Dashboard general" (helpdesk/reports/index.blade.php).
 *
 * Extraido del <script> inline de la vista para que el navegador lo cachee
 * en vez de re-descargarlo en cada carga de la página. La config dinámica
 * (datos de tendencia, distribución por estado y mensajes flash) llega por
 * window.HdReportsIndex, inyectada por Blade justo antes de este <script>.
 */
(function () {
    'use strict';

    var cfg = window.HdReportsIndex || {};
    var trendData = cfg.trend || { labels: [], series: [] };

    new Chart(document.getElementById('chart-trend'), {
        type: 'line',
        data: {
            labels: trendData.labels,
            datasets: [{
                label: 'Tickets creados',
                data: trendData.series,
                borderColor: '#90bb13',
                backgroundColor: 'rgba(144,187,19,0.12)',
                tension: 0.3,
                fill: true,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });

    var statusChartEl = document.getElementById('chart-status');
    if (statusChartEl) {
        var statusData = cfg.statusChart || [];

        new Chart(statusChartEl, {
            type: 'doughnut',
            data: {
                labels: statusData.map(function (d) { return d.label; }),
                datasets: [{
                    data: statusData.map(function (d) { return d.count; }),
                    backgroundColor: statusData.map(function (d) { return d.color; }),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: { legend: { display: false } },
            },
        });
    }

    $(document).ready(function () {
        if (cfg.flashSuccess) { toastr.success(cfg.flashSuccess, 'Exito'); }
        if (cfg.flashError) { toastr.error(cfg.flashError, 'Error'); }
    });
}());
