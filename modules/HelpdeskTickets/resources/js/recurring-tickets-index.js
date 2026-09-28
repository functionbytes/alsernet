/**
 * Listado de tickets recurrentes (managers/recurring-tickets/index.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Depende de
 * window.BulkActions / window.FilterShell (core, no se tocan),
 * window.HdtBulkListActions (bulk-list-actions.js, valida acción/ids y
 * envía el POST — mismo bloque antes duplicado en los 5 listados de este
 * módulo), jQuery, select2, toastr y window.hdtRecurringTicketsIndexConfig
 * (que publica el propio Blade como datos, no como lógica).
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtRecurringTicketsIndexConfig || {};
        var i18n = cfg.i18n || {};

        if (cfg.successMessage) { toastr.success(cfg.successMessage, i18n.successTitle || 'Exito'); }
        if (cfg.errorMessage) { toastr.error(cfg.errorMessage, i18n.errorTitle || 'Error'); }

        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });

        // ── Filtros avanzados ────────────────────────────────────────────
        $('.select2-filter-modal').select2({
            dropdownParent: window.FilterShell.el('rt-filter-modal'),
            width: '100%',
        });

        $('#rt-filter-apply-btn').on('click', function () {
            $('#rt-filter-frequency').val($('#modal-frequency').val());
            $('#rt-filter-category').val($('#modal-category').val());
            $('#rt-filter-status').val($('#modal-status').val());
            window.FilterShell.close('rt-filter-modal');
            $('#rt-filter-form').submit();
        });

        $('#rt-filter-clear-btn').on('click', function () {
            $('#modal-frequency, #modal-category, #modal-status').val(null).trigger('change');
        });

        // ── Acciones masivas ─────────────────────────────────────────────────
        if (document.querySelector('.bulk-checkbox')) {
            $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

            var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

            window.HdtBulkListActions.run({
                $modal: $('#bulk-modal'),
                $select: $('#bulk-action-select'),
                bulk: bulk,
                $applyBtn: $('#bulk-apply-btn'),
                url: cfg.bulkActionUrl,
                applyLabel: i18n.applyLabel || 'Aplicar',
                busyLabel: i18n.busyLabel || 'Procesando...',
                emptyActionMessage: i18n.chooseAction || 'Selecciona una accion.',
                emptyIdsMessage: i18n.chooseItems || 'Selecciona al menos un ticket recurrente.',
                errorMessage: i18n.genericError || 'Error al procesar.',
                confirmDeleteMessage: function (count) {
                    return (i18n.confirmDelete || '¿Eliminar los :count ticket(s) recurrente(s) seleccionado(s)? No se puede deshacer.').replace(':count', count);
                },
            });
        }
    });
})();
