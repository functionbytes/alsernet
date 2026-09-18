/**
 * HelpdeskAnalytics — dashboard.js
 *
 * Logic for modules/HelpdeskAnalytics/resources/views/dashboard/index.blade.php.
 * Depends on jQuery, Chart.js (loaded from CDN in the view) and toastr.
 *
 * User-facing strings and the data endpoint come from
 * window.HelpdeskAnalyticsDashboard, emitted inline by the view (see the
 * @push('scripts') block) since this file is a static asset with no access
 * to Blade's __() translations or route().
 */
(function () {
    'use strict';

    var CONFIG = window.HelpdeskAnalyticsDashboard || {};
    var i18n = CONFIG.i18n || {};

    function t(key, fallback) {
        return i18n[key] || fallback;
    }

    $(function () {
        var dataUrl = CONFIG.dataUrl;
        var charts = {};
        // Paleta de marca para las donuts (sin el rosado/multicolor por defecto de
        // Chart.js ni rojo, aunque sea semantico como "en riesgo"): mismos hex que
        // el donut de distribucion en Core/dashboard/index.blade.php.
        var donutPalette = ['#90bb13', '#4f6b0a', '#b6d34a', '#6c757d', '#adb5bd', '#333333'];

        function destroyChart(id) {
            if (charts[id]) { charts[id].destroy(); delete charts[id]; }
        }

        function secs(s) {
            if (!s) return '—';
            if (s < 60) return s + 's';
            if (s < 3600) return Math.round(s / 60) + 'm';
            return Math.round(s / 3600) + 'h';
        }

        function minutes(m) {
            if (!m) return '—';
            if (m < 60) return m + 'm';
            return Math.round(m / 60) + 'h';
        }

        // Mismo mapeo que Ticket::activityPriorityLabel() en HelpdeskTickets:
        // la API devuelve el slug crudo de la columna priority.
        var PRIORITY_LABELS = { urgent: 'Urgente', high: 'Alta', normal: 'Normal', low: 'Baja' };
        function priorityLabel(p) {
            return PRIORITY_LABELS[p] || p;
        }

        function renderAgents(rows) {
            if (!rows.length) {
                $('#agent-rows').html('<tr><td colspan="8" class="text-center text-muted py-3">' + t('noDataRange', 'No data in range.') + '</td></tr>');
                return;
            }
            $('#agent-rows').html(rows.map(function (a) {
                return '<tr>' +
                    '<td>' + $('<div>').text(a.name).html() + '</td>' +
                    '<td>' + a.closed_count + '</td>' +
                    '<td>' + (a.csat_avg || '—') + '</td>' +
                    '<td>' + secs(a.avg_response_seconds) + '</td>' +
                    '<td>' + a.message_count + '</td>' +
                    '<td>' + (a.ticket_closed_count || 0) + '</td>' +
                    '<td>' + minutes(a.ticket_avg_first_response_minutes) + '</td>' +
                    '<td>' + minutes(a.ticket_avg_resolution_minutes) + '</td>' +
                    '</tr>';
            }).join(''));
        }

        function renderTickets(t2) {
            t2 = t2 || {};
            $('#kpi-tickets-created').text(t2.total_created ?? 0);
            $('#kpi-tickets-closed').text(t2.total_closed ?? 0);
            $('#kpi-tickets-resolved').text(t2.total_resolved ?? 0);
            $('#kpi-tickets-sla-breached').text(t2.sla_breached ?? 0);
            $('#kpi-tickets-unassigned').text(t2.unassigned ?? 0);
            $('#kpi-tickets-frt').text(minutes(t2.avg_first_response_minutes));
            $('#kpi-tickets-resolution').text(minutes(t2.avg_resolution_minutes));

            var byPriority = t2.by_priority || [];

            if (!byPriority.length) {
                $('#ticket-priority-rows').html('<tr><td colspan="2" class="text-center text-muted py-3">' + t('noDataRange', 'No data in range.') + '</td></tr>');
                return;
            }
            $('#ticket-priority-rows').html(byPriority.map(function (p) {
                return '<tr>' +
                    '<td>' + $('<div>').text(priorityLabel(p.priority)).html() + '</td>' +
                    '<td>' + p.count + '</td>' +
                    '</tr>';
            }).join(''));
        }

        function load() {
            $.get(dataUrl, $('#filters').serialize()).done(function (res) {
                var o = res.overview || {};
                $('#kpi-conversations').text(o.conversations ?? 0);
                $('#kpi-closed').text(o.closed ?? 0);
                $('#kpi-open').text(o.open ?? 0);
                $('#kpi-frt').text(secs(o.avg_first_response_seconds));
                $('#kpi-csat').text(o.csat_avg ?? '—');

                var trends = res.trends || [];
                destroyChart('chart-trends');
                charts['chart-trends'] = new Chart(document.getElementById('chart-trends'), {
                    type: 'line',
                    data: {
                        labels: trends.map(function (tr) { return tr.date; }),
                        datasets: [
                            { label: t('created', 'Created'), data: trends.map(function (tr) { return tr.created; }), borderColor: '#90bb13', tension: 0.3 },
                            { label: t('closed', 'Closed'), data: trends.map(function (tr) { return tr.closed; }), borderColor: '#6c757d', tension: 0.3 },
                        ],
                    },
                    options: { responsive: true, maintainAspectRatio: false },
                });

                var channels = res.channels || [];
                destroyChart('chart-channels');
                charts['chart-channels'] = new Chart(document.getElementById('chart-channels'), {
                    type: 'doughnut',
                    data: { labels: channels.map(function (c) { return c.channel; }), datasets: [{ data: channels.map(function (c) { return c.count; }), backgroundColor: donutPalette }] },
                    options: { responsive: true, maintainAspectRatio: false },
                });

                var cust = res.customers || {};
                destroyChart('chart-customers');
                charts['chart-customers'] = new Chart(document.getElementById('chart-customers'), {
                    type: 'doughnut',
                    data: {
                        labels: [t('healthHealthy', 'Healthy'), t('healthNeutral', 'Neutral'), t('healthAtRisk', 'At risk')],
                        datasets: [{ data: [cust.healthy || 0, cust.neutral || 0, cust.at_risk || 0], backgroundColor: donutPalette }],
                    },
                    options: { responsive: true, maintainAspectRatio: false },
                });
                $('#cust-sampled').toggleClass('d-none', !cust.sampled);

                renderAgents(res.agents || []);
                renderTickets(res.tickets);
            }).fail(function () {
                if (window.toastr) { toastr.error(t('loadError', 'Metrics could not be loaded.')); }
            });
        }

        $('#filters').on('submit', function (e) { e.preventDefault(); load(); });
        load();
    });
})();
