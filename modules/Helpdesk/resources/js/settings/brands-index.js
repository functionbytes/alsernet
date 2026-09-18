/**
 * Helpdesk · Settings → Marcas (listado). Especifico de esta pantalla:
 * pintar el color de fondo de cada pastilla de color (viene en data-color
 * porque son colores dinamicos por marca, no se pueden precalcular en CSS),
 * el modal de filtros avanzados (estado) y la barra de acciones masivas
 * (activar / desactivar / eliminar, con confirmacion para el borrado).
 * Select2, el borrado individual y el flash de sesion los cubre
 * settings-common.js.
 */
(function ($) {
    'use strict';

    $(function () {
        const config = window.HdBrandsIndexConfig || {};

        $('.hd-color-swatch[data-color]').each(function () {
            $(this).css('background-color', $(this).data('color'));
        });

        HelpdeskSettingsCommon.filterModal({
            form: '#brands-filter-form',
            modal: '#brands-filter-modal',
            applyBtn: '#brands-filter-apply-btn',
            clearBtn: '#brands-filter-clear-btn',
            fields: [
                { hidden: '#filter-status', modal: '#modal-status' },
            ],
        });

        HelpdeskSettingsCommon.bulk({
            url: config.bulkUrl,
            emptyMessage: 'Selecciona al menos una marca.',
            confirmActions: {
                delete: '¿Eliminar {count} marca(s)? Esta acción no se puede deshacer.',
            },
        });
    });
})(jQuery);
