/*!
 * Helpdesk · reporte "Tendencias" (helpdesk/reports/trends.blade.php).
 *
 * Extraido del <script> inline de la vista para que el navegador lo cachee
 * en vez de re-descargarlo en cada carga de la página. La config dinámica
 * (URL del endpoint de datos) llega por window.HdReportsTrends, inyectada
 * por Blade justo antes de este <script>.
 */
(function () {
    'use strict';

    var cfg = window.HdReportsTrends || {};
    var dataUrl = cfg.dataUrl;

    var COLORS = ['#90bb13', '#13C672', '#FEC90F', '#FA896B', '#0084FF', '#6c757d', '#9c27b0', '#ff9800'];

    var charts = {};

    function destroyChart(id) {
        if (charts[id]) { charts[id].destroy(); delete charts[id]; }
    }

    function makeLine(id, labels, datasets) {
        destroyChart(id);
        var ctx = document.getElementById(id);
        if (!ctx) { return; }
        charts[id] = new Chart(ctx, {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                plugins: { legend: { display: datasets.length > 1 } },
                scales: { y: { beginAtZero: true } },
            },
        });
    }

    function makeBar(id, labels, datasets) {
        destroyChart(id);
        var ctx = document.getElementById(id);
        if (!ctx) { return; }
        charts[id] = new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                plugins: { legend: { display: true } },
                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
            },
        });
    }

    function load() {
        var params = {
            date_from: $('#filter-from').val() || '',
            date_to: $('#filter-to').val() || '',
            inbox_id: $('#filter-inbox').val() || '',
        };

        $.get(dataUrl, params).done(function (data) {
            var labels = data.labels;

            makeLine('chart-messages', labels, [
                { label: 'Mensajes recibidos', data: data.messages, borderColor: '#90bb13', backgroundColor: 'rgba(177,1,0,0.08)', tension: 0.3, fill: true },
            ]);

            makeLine('chart-created-closed', labels, [
                { label: 'Creadas', data: data.created, borderColor: '#0084FF', tension: 0.3, fill: false },
                { label: 'Resueltas', data: data.closed, borderColor: '#13C672', tension: 0.3, fill: false },
            ]);

            // Stacked bar for channel volume
            var channelDatasets = (data.channel_labels || []).map(function (ch, i) {
                return {
                    label: ch,
                    data: data.channel_series[ch] || [],
                    backgroundColor: COLORS[i % COLORS.length],
                };
            });
            makeBar('chart-channels', labels, channelDatasets);

            makeLine('chart-frt', labels, [
                { label: 'Primera respuesta (min)', data: data.first_response, borderColor: '#FEC90F', backgroundColor: 'rgba(254,201,15,0.08)', tension: 0.3, fill: true },
            ]);

            makeLine('chart-resolution', labels, [
                { label: 'Resolución (min)', data: data.resolution, borderColor: '#FA896B', backgroundColor: 'rgba(250,137,107,0.08)', tension: 0.3, fill: true },
            ]);

            makeLine('chart-csat', labels, [
                { label: 'CSAT promedio', data: data.csat, borderColor: '#13C672', backgroundColor: 'rgba(19,198,114,0.08)', tension: 0.3, fill: true },
            ]);
        }).fail(function () {
            toastr.error('Error al cargar los datos del reporte.');
        });
    }

    // Set default date range (last 30 days)
    var today = new Date();
    var prior = new Date(today);
    prior.setDate(prior.getDate() - 30);
    $('#filter-to').val(today.toISOString().slice(0, 10));
    $('#filter-from').val(prior.toISOString().slice(0, 10));

    $('#btn-apply').on('click', load);

    // Re-render visible charts when switching tabs (Chart.js needs visible canvas)
    document.querySelectorAll('#trends-tabs [data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () { load(); });
    });

    load();
}());
