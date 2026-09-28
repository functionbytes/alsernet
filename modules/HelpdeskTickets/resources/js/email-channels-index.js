/**
 * Listado de canales de correo (managers/settings/email-channels/index.blade.php)
 * — propiedad de HelpdeskTickets.
 *
 * Vivía como <script> suelto dentro del propio Blade. Depende de
 * window.BulkActions (core/js/bulk.js, no se toca), window.HdtBulkListActions
 * (bulk-list-actions.js, valida acción/ids y envía el POST — mismo bloque
 * antes duplicado en los 5 listados de este módulo), jQuery, select2, toastr
 * y window.hdtEmailChannelsIndexConfig (que publica el propio Blade como
 * datos, no como lógica).
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var cfg = window.hdtEmailChannelsIndexConfig || {};
        var i18n = cfg.i18n || {};

        if (cfg.successMessage) { toastr.success(cfg.successMessage, i18n.successTitle || 'Exito'); }
        if (cfg.errorMessage) { toastr.error(cfg.errorMessage, i18n.errorTitle || 'Error'); }

        // Delete modal
        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });

        // Sincronizar un canal concreto
        $(document).on('click', '.btn-sync-channel', function (e) {
            e.preventDefault();
            var $link = $(this);
            var original = $link.text();
            $link.text(i18n.syncing || 'Sincronizando...');

            $.ajax({
                url: cfg.syncUrlBase + '/' + $link.data('id') + '/sync',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    toastr.success(res.message);
                    setTimeout(function () { location.reload(); }, 1000);
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || i18n.syncError || 'Error inesperado al sincronizar el canal.');
                    $link.text(original);
                },
            });
        });

        // Bulk actions
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
            emptyActionMessage: i18n.chooseAction || 'Selecciona una accion.',
            emptyIdsMessage: i18n.chooseItems || 'Selecciona al menos un canal.',
            errorMessage: i18n.genericError || 'Error al procesar.',
            confirmDeleteMessage: function (count) {
                return (i18n.confirmDelete || '¿Eliminar los :count canal(es) seleccionados?').replace(':count', count);
            },
        });
    });
})();
