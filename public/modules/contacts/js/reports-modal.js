/**
 * Modal "Informes y clientes en riesgo" (contacts/partials/_reports-modal.blade.php).
 *
 * Resumen AJAX del dashboard completo (contacts.reports) — mismo payload
 * cacheado 2 min por agente, vía GET contacts.reports.summary. Solo pinta
 * top-3 en riesgo + 3 stats; "Ver los N en riesgo"/"Exportar informe" enlazan
 * a piezas ya existentes (vista guardada del listado, export con view=risk)
 * en vez de fingir un sistema de campañas que no existe en el backend.
 */
(function ($) {
    'use strict';

    var $modal = $('#contact-reports-modal');
    if (!$modal.length) {
        return;
    }

    var summaryUrl = $modal.data('summary-url');
    var loaded = false;

    function esc(str) {
        if (str == null) {
            return '';
        }
        return String(str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // "Un solo criterio de riesgo, explicado en cada fila" (mockup pieza 14):
    // nombre, motivo y puntuación de salud.
    function renderList(rows) {
        if (!rows.length) {
            return '<div class="psc-card-body"><div class="ct-note-box">Ningún contacto en riesgo con los criterios actuales.</div></div>';
        }

        return rows.map(function (r) {
            return '<a class="ct-risk-row" href="' + esc(r.url) + '">' +
                '<span class="n">' + esc(r.name) + '</span>' +
                '<span class="r">' + esc(r.reason) + '</span>' +
                (r.score != null ? '<span class="s">' + esc(r.score) + '</span>' : '') +
            '</a>';
        }).join('');
    }

    function load() {
        var $list = $('#contact-reports-list');
        $list.html('<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Cargando…</div>');

        $.ajax({
            url: summaryUrl,
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        }).done(function (resp) {
            var stats = (resp && resp.stats) || {};
            var atRisk = (resp && resp.atRisk) || [];

            $('#contact-reports-total').text(stats.total != null ? stats.total : '—');
            $('#contact-reports-risk').text(stats.atRisk != null ? stats.atRisk : '—');
            $('#contact-reports-health').text(stats.avgHealth != null ? stats.avgHealth : '—');

            $list.html(renderList(atRisk));

            if (stats.atRisk != null) {
                $('#contact-reports-view-btn').text('Ver los ' + stats.atRisk);
            }

            campaignIds = (resp && resp.campaignIds) || [];
            $('#contact-reports-campaign-btn')
                .toggleClass('d-none', !campaignIds.length || !window.ContactsSendHsm)
                .text('Enviar plantilla a ' + campaignIds.length + (campaignIds.length === 1 ? ' contacto' : ' contactos'));
        }).fail(function () {
            $list.html('<div class="ct-note-box">No se pudo cargar el informe. Inténtalo de nuevo.</div>');
        });
    }

    var campaignIds = [];

    // Envío masivo de plantilla a los en riesgo con WhatsApp (send-hsm-modal.js).
    $modal.on('click', '#contact-reports-campaign-btn', function () {
        $modal.modal('hide');
        window.ContactsSendHsm.openBulk(campaignIds, campaignIds.length, 0, 0);
    });

    $modal.on('show.bs.modal', function () {
        // Cacheado 2 min en el servidor: no hace falta recargar en cada
        // apertura dentro de esa ventana, solo la primera vez.
        if (!loaded) {
            loaded = true;
            load();
        }
    });
})(window.jQuery);
