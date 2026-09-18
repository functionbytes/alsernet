/**
 * Helpdesk · Settings → Configuracion de agentes (listado).
 * Especifico de esta pantalla: wiring del modal de filtros avanzados
 * (disponibilidad) y de la barra de acciones masivas (marcar disponible /
 * no disponible). El resto (select2, borrado, flash) lo cubre
 * settings-common.js automaticamente.
 */
(function ($) {
    'use strict';

    $(function () {
        const config = window.HdAgentSettingsIndexConfig || {};

        HelpdeskSettingsCommon.filterModal({
            form: '#agent-settings-filter-form',
            modal: '#agent-settings-filter-modal',
            applyBtn: '#agent-settings-filter-apply-btn',
            clearBtn: '#agent-settings-filter-clear-btn',
            fields: [
                { hidden: '#filter-available', modal: '#modal-available' },
            ],
        });

        HelpdeskSettingsCommon.bulk({
            url: config.bulkUrl,
            emptyMessage: 'Selecciona al menos un agente.',
        });
    });
})(jQuery);
