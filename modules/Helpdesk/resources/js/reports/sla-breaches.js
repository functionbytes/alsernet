/*!
 * Helpdesk · reporte "Incumplimientos SLA" (helpdesk/reports/sla-breaches.blade.php).
 *
 * Extraido del <script> inline de la vista para que el navegador lo cachee
 * en vez de re-descargarlo en cada carga de la página. La config dinámica
 * (URL del endpoint de datos y, si el bulk-assign está habilitado, la URL
 * del endpoint de reasignación) llega por window.HdReportsSlaBreaches,
 * inyectada por Blade justo antes de este <script>.
 */
(function () {
    'use strict';

    var cfg = window.HdReportsSlaBreaches || {};
    var dataUrl = cfg.dataUrl;
    var bulkUrl = cfg.bulkUrl || null;

    // Umbrales de las bandas de gravedad, en minutos. Están aquí y no
    // repartidos por el código porque los chips, el badge y el color de la
    // barrita tienen que coincidir; si se tocan, se tocan en un solo sitio.
    var BAND_HIGH = 48 * 60;
    var BAND_MID = 24 * 60;

    // Etiquetas base del select de banda — renderBandCounts() les añade el
    // conteo entre paréntesis cada vez que llegan datos nuevos.
    var BAND_LABELS = {
        all: 'Todos',
        high: 'Más de 48 h',
        mid: '24 – 48 h',
        low: 'Menos de 24 h',
    };

    // Ventana del bloque "próximos vencimientos": la misma que pide el
    // controlador a getUpcomingBreaches(24), y la que divide la barra.
    var UPCOMING_WINDOW_MIN = 24 * 60;

    var breached = [];
    var upcoming = [];
    var band = 'all';
    var agentFilter = '';
    var bulkBreached = null;
    var bulkUpcoming = null;

    function esc(value) {
        return $('<span>').text(value == null ? '' : value).html();
    }

    function formatDateTime(iso) {
        if (!iso) {
            return '—';
        }
        return new Date(iso).toLocaleString('es-ES', {
            day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        });
    }

    // "3 d 4 h" / "62 h 43 m" / "18 m" — sin decimales y sin unidades vacías.
    function formatDuration(minutes) {
        var total = Math.max(0, Math.round(minutes || 0));
        var hours = Math.floor(total / 60);
        var mins = total % 60;

        if (hours >= 48) {
            var days = Math.floor(hours / 24);
            var rest = hours % 24;
            return rest > 0 ? (days + ' d ' + rest + ' h') : (days + ' d');
        }

        if (hours > 0) {
            return mins > 0 ? (hours + ' h ' + mins + ' m') : (hours + ' h');
        }

        return mins + ' m';
    }

    function bandOf(minutes) {
        if (minutes >= BAND_HIGH) { return 'high'; }
        if (minutes >= BAND_MID) { return 'mid'; }
        return 'low';
    }

    function delayBadgeClass(severity) {
        if (severity === 'high') { return 'badge bg-warning text-dark'; }
        if (severity === 'mid') { return 'badge bg-warning-subtle text-warning-emphasis'; }
        return 'badge bg-light text-dark border';
    }

    function ticketLink(row) {
        var label = esc(row.number || ('#' + row.id));
        if (!row.url) {
            return '<span class="fw-semibold">' + label + '</span>';
        }
        return '<a href="' + row.url + '" class="fw-semibold text-decoration-none">' + label + '</a>';
    }

    function agentCell(row) {
        if (!row.agentId) {
            return '<span class="text-muted fst-italic">Sin asignar</span>';
        }
        return esc(row.agentName);
    }

    // Celda de selección para el bulk: vacía (ni siquiera la <td>) cuando no
    // hay endpoint de reasignación, para no descuadrar las columnas del thead.
    function checkboxCell(group, row) {
        if (!bulkUrl) {
            return '';
        }
        return '<td><input type="checkbox" class="form-check-input bulk-checkbox-' + group + '" value="' + row.id + '" aria-label="Seleccionar ticket ' + esc(row.number || ('#' + row.id)) + '"></td>';
    }

    function rowMenu(row) {
        if (!row.url) {
            return '<span class="text-muted">—</span>';
        }

        return '<div class="dropdown">' +
            '<a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">' +
            '<i class="fas fa-ellipsis-vertical"></i>' +
            '</a>' +
            '<ul class="dropdown-menu dropdown-menu-end">' +
            '<li><a class="dropdown-item" href="' + row.url + '">Abrir ticket</a></li>' +
            '</ul>' +
            '</div>';
    }

    // El endpoint devuelve los incumplidos agrupados por agente; el panel
    // necesita una sola tabla plana ordenada por retraso.
    function flatten(groups) {
        var rows = [];
        (groups || []).forEach(function (group) {
            (group.tickets || []).forEach(function (ticket) {
                rows.push(ticket);
            });
        });
        return rows.sort(function (a, b) {
            return (b.overdueMinutes || 0) - (a.overdueMinutes || 0);
        });
    }

    function matchesCommonFilters(row, term) {
        if (agentFilter !== '' && String(row.agentId || '') !== String(agentFilter)) {
            return false;
        }

        if (term === '') {
            return true;
        }

        var haystack = ((row.subject || '') + ' ' + (row.number || '')).toLowerCase();
        return haystack.indexOf(term) !== -1;
    }

    function visibleRows() {
        var term = ($('#slb-search').val() || '').toString().trim().toLowerCase();

        return breached.filter(function (row) {
            if (band !== 'all' && bandOf(row.overdueMinutes) !== band) {
                return false;
            }

            return matchesCommonFilters(row, term);
        });
    }

    function visibleUpcoming() {
        var term = ($('#slb-search').val() || '').toString().trim().toLowerCase();

        return upcoming.filter(function (row) {
            return matchesCommonFilters(row, term);
        });
    }

    function minutesUntil(iso) {
        if (!iso) { return 0; }
        return Math.max(0, (new Date(iso).getTime() - Date.now()) / 60000);
    }

    function renderKpis(payload) {
        var total = breached.length;
        var open = payload.openTotal || 0;

        $('#kpi-breached').text(total);
        $('#kpi-breached-hint').text(
            open > 0 ? ('de ' + open + ' ' + (open === 1 ? 'ticket abierto' : 'tickets abiertos')) : ' '
        );

        var withAgent = new Set();
        var unassigned = 0;
        breached.forEach(function (row) {
            if (row.agentId) { withAgent.add(row.agentId); } else { unassigned++; }
        });

        $('#kpi-agents').text(withAgent.size);
        $('#kpi-agents-hint').text(unassigned > 0 ? ('+ ' + unassigned + ' sin asignar') : 'ninguno sin asignar');

        if (total > 0) {
            var sum = breached.reduce(function (acc, row) { return acc + (row.overdueMinutes || 0); }, 0);
            $('#kpi-avg').text(formatDuration(sum / total));
            $('#kpi-avg-hint').text('el peor, ' + formatDuration(breached[0].overdueMinutes));
        } else {
            $('#kpi-avg').text('—');
            $('#kpi-avg-hint').text(' ');
        }

        $('#kpi-upcoming').text(upcoming.length);
        if (upcoming.length > 0) {
            $('#kpi-upcoming-hint').text('el primero, en ' + formatDuration(minutesUntil(upcoming[0].dueAt)));
        } else {
            $('#kpi-upcoming-hint').text('nada en la ventana');
        }
    }

    function renderBandCounts() {
        var counts = { all: breached.length, high: 0, mid: 0, low: 0 };
        breached.forEach(function (row) { counts[bandOf(row.overdueMinutes)]++; });

        var select = $('#slb-modal-band');
        var current = select.val();

        Object.keys(counts).forEach(function (key) {
            select.find('option[value="' + key + '"]').text(BAND_LABELS[key] + ' (' + counts[key] + ')');
        });

        // select2 cachea el texto de la opción ya seleccionada: un
        // trigger('change') no le hace releer el DOM si el value no cambió,
        // así que el conteo se quedaba pegado en "(0)" tras la primera
        // carga. Destruir y reinicializar el widget sí lo fuerza a repintar.
        if (select.data('select2')) {
            select.select2('destroy');
        }
        select.select2({ dropdownParent: $('#slb-filter-modal'), width: '100%' }).val(current).trigger('change');
    }

    function renderAgentOptions() {
        var select = $('#slb-modal-agent');
        var current = select.val();
        var seen = new Map();
        var hasUnassigned = false;

        breached.concat(upcoming).forEach(function (row) {
            if (row.agentId) {
                seen.set(row.agentId, row.agentName);
            } else {
                hasUnassigned = true;
            }
        });

        select.find('option:not(:first)').remove();
        seen.forEach(function (name, id) {
            select.append($('<option>').attr('value', id).text(name));
        });

        if (hasUnassigned) {
            select.append($('<option>').attr('value', '0').text('Sin asignar'));
        }

        select.val(current).trigger('change');
    }

    function renderFilterBadge() {
        var count = (agentFilter !== '' ? 1 : 0) + (band !== 'all' ? 1 : 0);
        $('#slb-filter-badge').text(count).toggleClass('d-none', count === 0);
    }

    // Refleja band/agentFilter en los controles del modal cada vez que se
    // abre: si se aplicó un filtro y se reabre el modal, debe verse marcado.
    function syncFilterModal() {
        $('#slb-modal-band').val(band).trigger('change');
    }

    function renderBreached() {
        var rows = visibleRows();
        var body = $('#slb-breached-body');

        if (rows.length === 0) {
            $('#slb-breached-wrap').addClass('d-none');
            $('#slb-breached-empty').removeClass('d-none');

            if (breached.length === 0) {
                $('#slb-breached-empty-title').text('Ningún SLA incumplido');
                $('#slb-breached-empty-text').text('Todos los tickets abiertos están dentro de plazo.');
            } else {
                $('#slb-breached-empty-title').text('Sin resultados');
                $('#slb-breached-empty-text').text('Ningún ticket incumplido encaja con este filtro.');
            }
            if (bulkBreached) { bulkBreached.reset(); }
            return;
        }

        $('#slb-breached-wrap').removeClass('d-none');
        $('#slb-breached-empty').addClass('d-none');

        var html = rows.map(function (row) {
            var severity = bandOf(row.overdueMinutes);

            return '<tr>' +
                checkboxCell('breached', row) +
                '<td>' + ticketLink(row) + '</td>' +
                '<td>' + esc(row.subject) + '</td>' +
                '<td>' + agentCell(row) + '</td>' +
                '<td class="text-muted small">' + formatDateTime(row.dueAt) + '</td>' +
                '<td><span class="' + delayBadgeClass(severity) + '">' + formatDuration(row.overdueMinutes) + '</span></td>' +
                '<td class="text-center">' + rowMenu(row) + '</td>' +
                '</tr>';
        }).join('');

        body.html(html);
        if (bulkBreached) { bulkBreached.reset(); }
        $('#slb-count').text(
            rows.length === breached.length
                ? (breached.length + ' ' + (breached.length === 1 ? 'ticket' : 'tickets'))
                : ('Mostrando ' + rows.length + ' de ' + breached.length)
        );
    }

    function renderUpcoming() {
        var rows = visibleUpcoming();
        var body = $('#slb-upcoming-body');

        if (rows.length === 0) {
            $('#slb-upcoming-wrap').addClass('d-none');
            $('#slb-upcoming-empty').removeClass('d-none');
            if (bulkUpcoming) { bulkUpcoming.reset(); }
            return;
        }

        $('#slb-upcoming-wrap').removeClass('d-none');
        $('#slb-upcoming-empty').addClass('d-none');

        var html = rows.map(function (row) {
            var left = minutesUntil(row.dueAt);
            // La barra se llena según lo que QUEDA: cuanto menos queda, menos
            // barra. Menos de 6 h se considera inminente y pasa a ámbar.
            var pct = Math.max(2, Math.min(100, Math.round((left / UPCOMING_WINDOW_MIN) * 100)));
            var soon = left <= 6 * 60;
            var barClass = soon ? 'bg-warning' : 'bg-success';
            var leftClass = soon ? 'fw-semibold text-warning-emphasis' : 'text-muted';

            return '<tr>' +
                checkboxCell('upcoming', row) +
                '<td>' + ticketLink(row) + '</td>' +
                '<td>' + esc(row.subject) + '</td>' +
                '<td>' + agentCell(row) + '</td>' +
                '<td class="text-muted small">' + formatDateTime(row.dueAt) + '</td>' +
                '<td>' +
                    '<div class="progress slb-progress bv-minw-140">' +
                        '<div class="progress-bar ' + barClass + ' bv-progress-fill--dynamic" style="--bv-progress-pct: ' + pct + '%"></div>' +
                    '</div>' +
                    '<div class="small ' + leftClass + '">en ' + formatDuration(left) + '</div>' +
                '</td>' +
                '<td class="text-center">' + rowMenu(row) + '</td>' +
                '</tr>';
        }).join('');

        body.html(html);
        if (bulkUpcoming) { bulkUpcoming.reset(); }
        $('#slb-upcoming-count').text(
            rows.length === upcoming.length
                ? (upcoming.length + ' ' + (upcoming.length === 1 ? 'ticket' : 'tickets'))
                : ('Mostrando ' + rows.length + ' de ' + upcoming.length)
        );
    }

    function exportCsv() {
        var rows = visibleRows();

        if (rows.length === 0) {
            toastr.info('No hay nada que exportar con los filtros actuales.');
            return;
        }

        var header = ['Ticket', 'Asunto', 'Agente', 'Vencia', 'Retraso (minutos)'];
        var lines = [header].concat(rows.map(function (row) {
            return [
                row.number || ('#' + row.id),
                row.subject || '',
                row.agentId ? row.agentName : 'Sin asignar',
                row.dueAt || '',
                Math.round(row.overdueMinutes || 0)
            ];
        }));

        var csv = lines.map(function (cells) {
            return cells.map(function (cell) {
                return '"' + String(cell).replace(/"/g, '""') + '"';
            }).join(';');
        }).join('\n');

        // BOM para que Excel abra las tildes bien.
        var blob = new Blob(["﻿" + csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'incumplimientos-sla.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    function renderAll(data) {
        renderKpis(data);
        renderBandCounts();
        renderAgentOptions();
        renderFilterBadge();
        renderBreached();
        renderUpcoming();
    }

    function load() {
        var colCount = bulkUrl ? 7 : 6;

        $('#state-unavailable').addClass('d-none');
        $('#state-content').removeClass('d-none');
        $('#slb-breached-body').html('<tr><td colspan="' + colCount + '" class="text-center py-5 text-muted">Cargando datos…</td></tr>');
        $('#slb-upcoming-body').html('<tr><td colspan="' + colCount + '" class="text-center py-5 text-muted">Cargando datos…</td></tr>');
        $('#slb-breached-wrap, #slb-upcoming-wrap').removeClass('d-none');
        $('#slb-breached-empty, #slb-upcoming-empty').addClass('d-none');

        $.getJSON(dataUrl).done(function (data) {
            if (!data.available) {
                $('#state-content').addClass('d-none');
                $('#state-unavailable').removeClass('d-none');
                return;
            }

            breached = flatten(data.breachedByAgent);
            upcoming = (data.upcoming || []).slice().sort(function (a, b) {
                return new Date(a.dueAt) - new Date(b.dueAt);
            });

            renderAll(data);

            $('#slb-updated').text(
                'Actualizado a las ' + new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
            );
        }).fail(function () {
            toastr.error('Error al cargar el reporte de incumplimientos SLA.');
        });
    }

    $('#btn-refresh').on('click', load);
    $('#btn-export').on('click', exportCsv);

    $('#slb-search').on('input', function () {
        renderBreached();
        renderUpcoming();
    });

    $('.select2-filter-modal').select2({ dropdownParent: $('#slb-filter-modal'), width: '100%' });

    $('#slb-filter-modal').on('show.bs.modal', syncFilterModal);

    $('#slb-filter-apply-btn').on('click', function () {
        agentFilter = $('#slb-modal-agent').val() || '';
        band = $('#slb-modal-band').val() || 'all';
        $('#slb-filter-modal').modal('hide');
        renderFilterBadge();
        renderBreached();
        renderUpcoming();
    });

    $('#slb-filter-clear-btn').on('click', function () {
        $('#slb-modal-agent').val(null).trigger('change');
        $('#slb-modal-band').val('all').trigger('change');
    });

    // ── Bulk: reasignar tickets seleccionados a un agente ──────────────────
    // Reusa el endpoint bulk de HelpdeskTickets (mismo que la bulk-bar del
    // listado de tickets), así que el payload es el suyo: ticket_ids[] +
    // action + agent_id, no {action, ids} como el resto de bulk-actions de
    // este proyecto.
    function submitBulkAssign(group, bulk) {
        var select = $('#slb-bulk-' + group + '-agent');
        var agentId = select.val();
        var ids = bulk.getIds();

        if (!ids.length) { toastr.warning('Selecciona al menos un ticket.'); return; }
        if (!agentId) { toastr.warning('Selecciona un agente.'); return; }

        var btn = $('#slb-bulk-' + group + '-apply-btn');
        btn.prop('disabled', true).text('Reasignando...');

        $.ajax({
            url: bulkUrl,
            method: 'POST',
            data: JSON.stringify({
                ticket_ids: ids.map(Number),
                action: 'assign',
                agent_id: Number(agentId),
            }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-' + group + '-modal').modal('hide');
                toastr.success(res.message);
                load();
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al reasignar los tickets.');
            },
            complete: function () {
                btn.prop('disabled', false).text('Reasignar');
            },
        });
    }

    if (bulkUrl) {
        bulkBreached = window.BulkActions.init({
            checkbox: '.bulk-checkbox-breached',
            toolbar: '#bulk-toolbar-breached',
            selectAll: '#slb-select-all-breached',
        });
        bulkUpcoming = window.BulkActions.init({
            checkbox: '.bulk-checkbox-upcoming',
            toolbar: '#bulk-toolbar-upcoming',
            selectAll: '#slb-select-all-upcoming',
        });

        $('#slb-bulk-breached-agent').select2({ dropdownParent: $('#bulk-breached-modal'), width: '100%' });
        $('#slb-bulk-upcoming-agent').select2({ dropdownParent: $('#bulk-upcoming-modal'), width: '100%' });

        $('#slb-bulk-breached-apply-btn').on('click', function () { submitBulkAssign('breached', bulkBreached); });
        $('#slb-bulk-upcoming-apply-btn').on('click', function () { submitBulkAssign('upcoming', bulkUpcoming); });

        $('#bulk-breached-modal, #bulk-upcoming-modal').on('hide.bs.modal', function () {
            $(this).find('select').val(null).trigger('change');
            $(this).find('button[id$="-apply-btn"]').prop('disabled', false).text('Reasignar');
        });
    }

    load();
}());
