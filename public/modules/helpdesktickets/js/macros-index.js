/**
 * Listado de macros (managers/settings/macros/index.blade.php) — propiedad
 * de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Depende de
 * window.BulkActions (core/js/bulk.js, no se toca), window.HdtBulkListActions
 * (bulk-list-actions.js, valida acción/ids y envía el POST — mismo bloque
 * antes duplicado en los 5 listados de este módulo), jQuery, select2, toastr
 * y window.hdtMacrosIndexConfig (que publica el propio Blade como datos, no
 * como lógica).
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtMacrosIndexConfig || {};
        var i18n = cfg.i18n || {};

        var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });
        $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

        window.HdtBulkListActions.run({
            $modal: $('#bulk-modal'),
            $select: $('#bulk-action-select'),
            bulk: bulk,
            $applyBtn: $('#bulk-apply-btn'),
            url: cfg.bulkActionUrl,
            applyLabel: i18n.applyLabel || 'Aplicar',
            busyLabel: i18n.busyLabel || 'Procesando...',
            emptyActionMessage: i18n.chooseAction || 'Selecciona una acción.',
            emptyIdsMessage: i18n.chooseItems || 'Selecciona al menos una macro.',
            errorMessage: i18n.genericError || 'Error al procesar.',
            confirmDeleteMessage: function (count) {
                return (i18n.confirmDelete || '¿Eliminar las :count macro(s) seleccionadas?').replace(':count', count);
            },
        });
    });
})();
