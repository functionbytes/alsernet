/*!
 * Helpdesk · reporte "Clientes en riesgo" (helpdesk/reports/at-risk.blade.php).
 *
 * Extraido del <script> inline de la vista para que el navegador lo cachee
 * en vez de re-descargarlo en cada carga de la página. La config dinámica
 * (URL del endpoint de datos) llega por window.HdReportsAtRisk, inyectada
 * por Blade justo antes de este <script>.
 */
$(function () {
    'use strict';

    var cfg = window.HdReportsAtRisk || {};
    var dataUrl = cfg.dataUrl;

    var allCustomers = [];
    var riskFilter = '';
    var bulk = null;

    var riskLabels = { high: 'Alto (< 40)', medium: 'Medio (40 - 69)', good: 'Bueno (≥ 70)' };

    function esc(value) {
        return $('<span>').text(value == null ? '' : value).html();
    }

    function healthBadge(score) {
        // Sin rojos: gris neutro (bg-info-subtle) para riesgo alto/medio,
        // verde (bg-success-subtle) solo para salud buena. En este tema
        // bg-secondary-subtle/text-success resuelven al mismo verde de marca,
        // asi que el "gris" real que pidieron es bg-info-subtle.
        var cls = score >= 70 ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info';
        return '<span class="badge ' + cls + '">' + score + '</span>';
    }

    function negativeBadge(count) {
        return '<span class="badge bg-info-subtle text-info">' + count + '</span>';
    }

    function formatDate(iso) {
        if (!iso) {
            return '—';
        }
        return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function riskLevelOf(score) {
        if (score < 40) {
            return 'high';
        }
        return score < 70 ? 'medium' : 'good';
    }

    function renderRows(rows) {
        var tbody = $('#tbody-at-risk');
        tbody.empty();

        if (rows.length === 0) {
            var message = allCustomers.length === 0
                ? 'No hay clientes en riesgo en los últimos 90 días.'
                : 'Ningún cliente coincide con los filtros aplicados.';

            tbody.html(
                '<tr><td colspan="6" class="text-center text-muted py-5">' +
                '<i class="fas fa-heart fa-2x mb-2 d-block text-success"></i>' +
                message +
                '</td></tr>'
            );
            return;
        }

        rows.forEach(function (row) {
            // Sin boton "Ver ficha": la fila completa navega a la ficha del
            // cliente al hacer click (salvo el checkbox de seleccion masiva).
            var rowAttrs = row.contactUrl
                ? ' class="ar-row-clickable" data-contact-url="' + row.contactUrl + '"'
                : '';
            tbody.append(
                '<tr' + rowAttrs + '>' +
                '<td><input type="checkbox" class="form-check-input bulk-checkbox-ar" value="' + row.customerId + '" aria-label="Seleccionar cliente ' + esc(row.name) + '"></td>' +
                '<td class="fw-semibold">' + esc(row.name) + '</td>' +
                '<td class="text-muted">' + esc(row.email || '—') + '</td>' +
                '<td class="text-center">' + negativeBadge(row.negativeCount) + '</td>' +
                '<td class="text-center">' + healthBadge(row.healthScore) + '</td>' +
                '<td class="text-nowrap text-muted small">' + formatDate(row.lastNegativeAt) + '</td>' +
                '</tr>'
            );
        });

        // Las filas se repintan enteras en cada búsqueda/filtro/refresco — los
        // checkboxes de la tanda anterior ya no existen, así que la barra
        // flotante y el contador deben volver a 0 en vez de arrastrar una
        // selección de filas que ya no están en el DOM.
        if (bulk) bulk.reset();
    }

    function updateStats(customers) {
        $('#ar-stat-total').text(customers.length);
        $('#ar-stat-high').text(customers.filter(function (c) { return c.healthScore < 40; }).length);
        $('#ar-stat-medium').text(customers.filter(function (c) { return c.healthScore >= 40 && c.healthScore < 70; }).length);

        var latest = null;
        customers.forEach(function (c) {
            if (c.lastNegativeAt && (!latest || new Date(c.lastNegativeAt) > new Date(latest))) {
                latest = c.lastNegativeAt;
            }
        });
        $('#ar-stat-last-negative').text(formatDate(latest));
    }

    function updateFilterUi(search) {
        var hasFilters = !!search || !!riskFilter;

        $('#ar-filter-badge').toggleClass('d-none', !riskFilter);
        $('#ar-clear-btn').toggleClass('d-none', !hasFilters);
        $('#ar-filter-tags').toggleClass('d-none', !hasFilters);

        var tagsList = $('#ar-filter-tags-list').empty();
        if (search) {
            tagsList.append('<span class="badge bg-primary-subtle text-primary py-1 px-2">Búsqueda: ' + esc(search) + '</span>');
        }
        if (riskFilter) {
            tagsList.append('<span class="badge bg-primary-subtle text-primary py-1 px-2">Riesgo: ' + esc(riskLabels[riskFilter]) + '</span>');
        }
    }

    function applyFilters() {
        var search = $('#ar-search').val().trim();
        var searchLower = search.toLowerCase();

        var filtered = allCustomers.filter(function (c) {
            var matchesSearch = !searchLower ||
                (c.name && c.name.toLowerCase().indexOf(searchLower) !== -1) ||
                (c.email && c.email.toLowerCase().indexOf(searchLower) !== -1);
            var matchesRisk = !riskFilter || riskLevelOf(c.healthScore) === riskFilter;
            return matchesSearch && matchesRisk;
        });

        renderRows(filtered);
        updateFilterUi(search);
    }

    function load() {
        var tbody = $('#tbody-at-risk');
        tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">Cargando datos...</td></tr>');

        $.getJSON(dataUrl).done(function (data) {
            allCustomers = data.customers || [];
            updateStats(allCustomers);
            applyFilters();
        }).fail(function () {
            tbody.html('<tr><td colspan="6" class="text-center text-dark py-4">Error al cargar los datos.</td></tr>');
            toastr.error('Error al cargar el reporte de clientes en riesgo.');
        });
    }

    $('.select2-filter-modal').select2({ dropdownParent: $('#ar-filter-modal'), width: '100%' });

    // Selección múltiple: solo la mecánica de marcar/contar/mostrar la barra
    // (BulkActions ya la resuelve igual que en el resto de listados con
    // bulk.js). Sin acción real todavía — este reporte es de solo lectura,
    // calculado al vuelo (sentimientos + salud), no hay ningún estado
    // "revisado"/"descartado" guardado en BD que una acción masiva pueda
    // tocar hoy — así que el botón queda deshabilitado con una nota en vez
    // de simular una acción que no existe.
    bulk = window.BulkActions.init({
        checkbox: '.bulk-checkbox-ar',
        toolbar: '#bulk-toolbar-ar',
        selectAll: '#ar-select-all',
    });

    // Fila clicable hacia la ficha del cliente (reemplaza el boton "Ver
    // ficha"). Se ignora el click cuando viene del checkbox de seleccion.
    $('#tbody-at-risk').on('click', 'tr.ar-row-clickable', function (e) {
        if ($(e.target).closest('td').is(':first-child')) {
            return;
        }
        window.location.href = $(this).data('contact-url');
    });

    $('#ar-filter-form').on('submit', function (e) {
        e.preventDefault();
        applyFilters();
    });

    $('#ar-clear-btn').on('click', function () {
        $('#ar-search').val('');
        riskFilter = '';
        $('#ar-modal-risk').val(null).trigger('change');
        applyFilters();
    });

    $('#ar-filter-apply-btn').on('click', function () {
        riskFilter = $('#ar-modal-risk').val() || '';
        $('#ar-filter-modal').modal('hide');
        applyFilters();
    });

    $('#ar-filter-clear-btn').on('click', function () {
        $('#ar-modal-risk').val(null).trigger('change');
    });

    $('#btn-refresh').on('click', load);

    load();
});
