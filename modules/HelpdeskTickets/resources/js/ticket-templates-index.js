/**
 * Listado de plantillas de ticket (managers/ticket-templates/index.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Depende de
 * window.BulkActions (core/js/bulk.js, no se toca), window.HdtBulkListActions
 * (bulk-list-actions.js, valida acción/ids y envía el POST — mismo bloque
 * antes duplicado en los 5 listados de este módulo, aquí una vez por
 * pestaña), jQuery, select2, toastr y window.hdtTicketTemplatesIndexConfig
 * (que publica el propio Blade como datos, no como lógica).
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtTicketTemplatesIndexConfig || {};
        var i18n = cfg.i18n || {};

        if (cfg.successMessage) { toastr.success(cfg.successMessage, i18n.successTitle || 'Exito'); }
        if (cfg.errorMessage) { toastr.error(cfg.errorMessage, i18n.errorTitle || 'Error'); }

        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });

        // ── Filtros avanzados ────────────────────────────────────────────
        $('.select2-filter-modal').select2({ dropdownParent: $('#tt-filter-modal'), width: '100%' });

        $('#tt-filter-apply-btn').on('click', function () {
            $('#tt-filter-category').val($('#tt-modal-category').val());
            $('#tt-filter-priority').val($('#tt-modal-priority').val());
            $('#tt-filter-status').val($('#tt-modal-status').val());
            $('#tt-filter-modal').modal('hide');
            $('#tt-filter-form').submit();
        });

        $('#tt-filter-clear-btn').on('click', function () {
            $('#tt-modal-category, #tt-modal-priority, #tt-modal-status').val(null).trigger('change');
        });

        // ── Bulk: un HdtBulkListActions.run() por pestaña (Generales / Mis plantillas) ──
        ['general', 'mine'].forEach(function (group) {
            $('#bulk-' + group + '-action-select').select2({ dropdownParent: $('#bulk-' + group + '-modal'), width: '100%' });

            var bulk = window.BulkActions.init({
                checkbox: '.bulk-checkbox-' + group,
                toolbar: '#bulk-toolbar-' + group,
                selectAll: '#select-all-' + group,
            });

            window.HdtBulkListActions.run({
                $modal: $('#bulk-' + group + '-modal'),
                $select: $('#bulk-' + group + '-action-select'),
                bulk: bulk,
                $applyBtn: $('#bulk-' + group + '-apply-btn'),
                url: cfg.bulkActionUrl,
                applyLabel: i18n.applyLabel || 'Aplicar',
                busyLabel: i18n.busyLabel || 'Procesando...',
                emptyActionMessage: i18n.chooseAction || 'Selecciona una acción.',
                emptyIdsMessage: i18n.chooseItems || 'Selecciona al menos una plantilla.',
                errorMessage: i18n.genericError || 'Error al procesar.',
                confirmDeleteMessage: function (count) {
                    return (i18n.confirmDelete || '¿Eliminar las :count plantilla(s) seleccionada(s)? No se puede deshacer.').replace(':count', count);
                },
            });
        });
    });
})();
