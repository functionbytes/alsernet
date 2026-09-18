/**
 * Helpdesk · Settings → Banners (listado). Especifico de esta pantalla:
 * wiring del modal de filtros avanzados (tipo, estado) y de la barra de
 * acciones masivas (activar / desactivar / eliminar, con confirmacion para
 * el borrado). Select2, el borrado individual y el flash de sesion los
 * cubre settings-common.js.
 */
(function ($) {
    'use strict';

    $(function () {
        const config = window.HdBannersIndexConfig || {};

        HelpdeskSettingsCommon.filterModal({
            form: '#banners-filter-form',
            modal: '#banners-filter-modal',
            applyBtn: '#banners-filter-apply-btn',
            clearBtn: '#banners-filter-clear-btn',
            fields: [
                { hidden: '#filter-type', modal: '#modal-type' },
                { hidden: '#filter-status', modal: '#modal-status' },
            ],
        });

        HelpdeskSettingsCommon.bulk({
            url: config.bulkUrl,
            emptyMessage: 'Selecciona al menos un banner.',
            confirmActions: {
                delete: '¿Eliminar los {count} banner(s) seleccionados?',
            },
        });
    });
})(jQuery);
